    <?php
    /**
    * Novalnet payment method module
    * This module is used for real time processing of
    * Novalnet transaction of customers.
    *
    * Copyright (c) Novalnet
    *
    * Released under the GNU General Public License
    * This free contribution made by request.
    * If you have found this script useful a small
    * recommendation as well as a comment on merchant form
    * would be greatly appreciated.
    *
    * Script: Novalnet.callback.class.php
    *
    */
    require_once('includes/globalinclude.php');
    require_once(PFAD_INCLUDES_MODULES . 'PaymentMethod.class.php');
    require_once(PFAD_ROOT. PFAD_CLASSES . 'class.JTL-Shop.Plugin.php');

    /**
    * Handles callback request
    *
    * @param none
    * @return none
    */
    function performCallbackExecution()
    {
    global $processTestMode, $oPlugin, $jtlPaymentClass, $DB, $shopQuery, $novalnetOrderLanguage, $selectQuery;

    // Get order object
    $oPlugin = Plugin::getPluginById('novalnetag');

    require_once(PFAD_PLUGIN . $oPlugin->cPluginID . '/' . PFAD_PLUGIN_VERSION . $oPlugin->nVersion . '/' . PFAD_PLUGIN_PAYMENTMETHOD . PFAD_CLASSES . 'NovalnetGateway.class.php');
    require_once(PFAD_INCLUDES . 'mailTools.php');

    $jtlPaymentClass = NovalnetGateway::getInstance(); // Get instance of NovalnetGateway class

    $callbackRequestParams = $_REQUEST;
    // Update into false when switching into LIVE MODE
    $processTestMode  = $oPlugin->oPluginEinstellungAssoc_arr['callback_testmode'];

    $jtlVendorScript  = new NovalnetVendorScript($callbackRequestParams); // Get instance of NovalnetVendorScript class

    $sendOrderMail = false;

    // Affiliate process
    if (!empty($callbackRequestParams['vendor_activation'])) {
    $jtlVendorScript->updateAffiliateDatas($callbackRequestParams); // Logs affiliate details into database

    $callbackScriptText = 'Novalnet callback script executed successfully with Novalnet account activation information.';

    // Send notification mail to Merchant
    $jtlVendorScript->sendMailNotification(array(
        'comments' => $callbackScriptText
    ));
    $jtlVendorScript->displayMessage($callbackScriptText);
    } else {
    $jtlVendorParams  = $jtlVendorScript->arycaptureparams; // Callback request params
    // Loads the corresponding order object based on request
    $jtlVendorHistory = $jtlVendorScript->getOrderByIncrementId($jtlVendorParams);
    $orderAmount      = (string)$jtlVendorHistory->fGesamtsumme;

    // Retrieves the language variables based on the end-user's order language
    $novalnetOrderLanguage = nnLoadOrderLanguage($oPlugin->kPlugin, $jtlVendorHistory->kSprache);

    $orderId = $jtlVendorHistory->cBestellNr;

    $callbackScriptText ='';
    // Determine the payment type level to which the requested payment belongs
    $paymentLevel   = $jtlVendorScript->getPaymentTypeLevel();

    $paymentSuccess = $jtlVendorParams['status'] == 100 && $jtlVendorParams['tid_status'] == 100;

    $currencyObj = $DB->$selectQuery(
        'twaehrung',
        'kWaehrung',
        $jtlVendorHistory->kWaehrung
    );

    // Cancellation of a Subscription
    if ($jtlVendorParams['payment_type'] == 'SUBSCRIPTION_STOP') {
        if (!empty($jtlVendorParams['termination_reason'])) {
            NovalnetGateway::performDbExecution('xplugin_novalnetag_tsubscription_details', 'cTerminationReason = "' . $jtlVendorParams['termination_reason'] . '", dTerminationAt = now()', 'nTid="' . $jtlVendorParams['shop_tid'] . '"'); // Updates the value into the database
        }

        $callbackScriptText = sprintf($novalnetOrderLanguage['__NN_callback_subscription_cancellation'], $jtlVendorParams['shop_tid'], date('d.m.Y'), date('H:i:s')) . PHP_EOL . $novalnetOrderLanguage['__NN_subscription_cancelled'] . $jtlVendorParams['termination_reason'];

        NovalnetGateway::performDbExecution('tbestellung', 'cStatus=' . constant($oPlugin->oPluginEinstellungAssoc_arr['subscription_order_status']), 'cBestellNr = "' . $orderId . '"'); // Updates the value into the database
        // Completes the callback execution
        $jtlVendorScript->callbackFinalProcess($callbackScriptText, $jtlVendorHistory);
    } elseif($jtlVendorParams['payment_type'] == 'SUBSCRIPTION_REACTIVATE') {

        $callbackScriptText = ' Novalnet callback script received. Subscription has been reactivated for the TID:'. $jtlVendorParams['shop_tid']. ' on ' . date('Y-m-d H:i:s');

        NovalnetGateway::performDbExecution('xplugin_novalnetag_tsubscription_details', 'cTerminationReason = "", dTerminationAt = ""', 'nTid="' . $jtlVendorParams['shop_tid'] . '"'); // Updates the value into the database
        NovalnetGateway::performDbExecution('xplugin_novalnetag_tnovalnet_status', 'nStatuswert = "' . $jtlVendorParams['tid_status'] . '"', 'nNntid="' . $jtlVendorParams['shop_tid'] . '"'); // Updates the value into the database

        $jtlVendorScript->callbackFinalProcess($callbackScriptText, $jtlVendorHistory);

        $jtlVendorScript->displayMessage($callbackScriptText);
    } elseif($jtlVendorParams['payment_type'] == 'TRANSACTION_CANCELLATION' && in_array($jtlVendorHistory->cNnStatus, array('75','91','99'))) {
        $cancelReason = $jtlPaymentClass->getResponseText($jtlVendorParams);

        $callbackScriptText = PHP_EOL . $novalnetOrderLanguage['__NN_callback_transaction_cancellation'] . $cancelReason;

        NovalnetGateway::performDbExecution('tbestellung', 'cStatus=' . constant($oPlugin->oPluginEinstellungAssoc_arr['cancel_order_status']), 'cBestellNr = "' . $orderId . '"'); // Updates the value into the database

        NovalnetGateway::performDbExecution('xplugin_novalnetag_tnovalnet_status', 'nStatuswert = "' . $jtlVendorParams['tid_status'] . '"', 'cNnorderid="' . $orderId . '"'); // Updates the value into the database
                // Completes the callback execution
        $jtlVendorScript->callbackFinalProcess($callbackScriptText, $jtlVendorHistory);
    }

    switch ($paymentLevel) {
        case '2':
            if ($paymentSuccess) {
                if (in_array($jtlVendorParams['payment_type'], array(
                                                                    'INVOICE_CREDIT',
                                                                    'CASHPAYMENT_CREDIT',
                                                                    'ONLINE_TRANSFER_CREDIT'
                                                            ))) {
                    $query = $DB->$shopQuery('SELECT SUM(nCallbackAmount) AS kCallbackAmount FROM xplugin_novalnetag_tcallback WHERE cBestellnummer = "' . $orderId . '"', 1);

                    $orderPaidAmount = $query->kCallbackAmount + $jtlVendorParams['amount'];

                    if ($query->kCallbackAmount < $orderAmount) {
                        $callbackScriptText = PHP_EOL . sprintf(
                            $novalnetOrderLanguage['__NN_callback_initial_execution'],
                            $jtlVendorParams['shop_tid'],
                            gibPreisLocalizedOhneFaktor($jtlVendorParams['amount'] / 100, $currencyObj, 0),
                            date('d.m.Y'),
                            date('H:i:s'),
                            $jtlVendorParams['tid']
                        );

                        if ($orderPaidAmount >= $orderAmount) {
                            // Retrieves payment object from class PaymentMethod
                            $jtlPaymentmethod = PaymentMethod::create($jtlVendorHistory->kPaymentId);

                            $incomingPayment           = new stdClass();
                            $incomingPayment->fBetrag  = $orderAmount / 100;
                            $incomingPayment->cISO     = $jtlVendorHistory->cCurrencyISO;
                            $incomingPayment->cHinweis = $jtlVendorParams['shop_tid'];
                            $jtlPaymentmethod->name    = $jtlVendorHistory->cZahlungsartName;
                             // Adds the current transaction into the shop's order table
                            $jtlPaymentmethod->addIncomingPayment($jtlVendorHistory, $incomingPayment);

                            if ($jtlVendorParams['payment_type'] == 'ONLINE_TRANSFER_CREDIT') {
                                $callbackScriptText = sprintf(
                                    $novalnetOrderLanguage['__NN_callback_status_change'],
                                    gibPreisLocalizedOhneFaktor($jtlVendorParams['amount'] / 100, $currencyObj, 0),
                                    $orderId
                                );

                                NovalnetGateway::performDbExecution('xplugin_novalnetag_tnovalnet_status', 'nStatuswert = "' . $jtlVendorParams['tid_status'] . '"', 'cNnorderid="' . $orderId . '"');

                                NovalnetGateway::performDbExecution(
                                    'tbestellung',
                                    'dBezahltDatum = now(), cAbgeholt="N"',
                                    'cBestellNr = "' . $orderId . '"'
                                ); // Updates the value into the database
                            } else {
                                NovalnetGateway::performDbExecution('tbestellung', 'dBezahltDatum = now(), cStatus=' . constant($oPlugin->oPluginEinstellungAssoc_arr[$jtlVendorHistory->cPaymentType.'_callback_status']), 'cBestellNr = "' . $orderId . '"'); // Updates the value into the database
                            }
                        }

                        $jtlVendorScript->callbackFinalProcess(
                            $callbackScriptText,
                            $jtlVendorHistory,
                            $orderPaidAmount > $orderAmount
                        );

                        $jtlVendorScript->displayMessage($callbackScriptText);
                    }

                    $jtlVendorScript->displayMessage('Novalnet callback received. Callback Script executed already', $orderId);
                } else {
                    $callbackScriptText = PHP_EOL . sprintf(
                        $novalnetOrderLanguage['__NN_callback_initial_execution'],
                        $jtlVendorParams['shop_tid'],
                        gibPreisLocalizedOhneFaktor($jtlVendorParams['amount'] / 100, $currencyObj, 0),
                        date('d.m.Y'),
                        date('H:i:s'),
                        $jtlVendorParams['tid']
                    );

                    $jtlVendorScript->callbackFinalProcess($callbackScriptText, $jtlVendorHistory);
                }

                $jtlVendorScript->displayMessage('Novalnet Callbackscript received. Payment type ( ' . $jtlVendorParams['payment_type'] . ' ) is not applicable for this process!', $orderId);
            }
            break;

        case '1':
            if ($paymentSuccess) {
                $callbackScriptText = in_array($jtlVendorParams['payment_type'], array('CREDITCARD_BOOKBACK', 'PAYPAL_BOOKBACK', 'PRZELEWY24_REFUND', 'CASHPAYMENT_REFUND', 'GUARANTEED_INVOICE_BOOKBACK', 'GUARANTEED_SEPA_BOOKBACK', 'REFUND_BY_BANK_TRANSFER_EU')) ? PHP_EOL . sprintf($novalnetOrderLanguage['__NN_callback_bookback_execution'], $jtlVendorParams['shop_tid'], gibPreisLocalizedOhneFaktor($jtlVendorParams['amount'] / 100, $currencyObj, 0), date('d.m.Y'), date('H:i:s'), $jtlVendorParams['tid']) : PHP_EOL . sprintf($novalnetOrderLanguage['__NN_callback_chargeback_execution'], $jtlVendorParams['shop_tid'], gibPreisLocalizedOhneFaktor($jtlVendorParams['amount'] / 100, $currencyObj, 0), date('d.m.Y'), date('H:i:s'), $jtlVendorParams['tid']);
                 // Completes the callback execution
                $jtlVendorScript->callbackFinalProcess($callbackScriptText, $jtlVendorHistory);
            }
            break;

        case '0':
            if ($jtlVendorParams['status'] == 100 && in_array($jtlVendorParams['tid_status'], array(85, 86, 90, 91, 98, 99, 100))) {
                if (isset($jtlVendorParams['subs_billing']) && $jtlVendorParams['subs_billing'] == '1') {
                    $callbackScriptText = sprintf(
                        $novalnetOrderLanguage['__NN_callback_subscription_execution'],
                        $jtlVendorParams['shop_tid'],
                        gibPreisLocalizedOhneFaktor($jtlVendorParams['amount'] / 100, $currencyObj, 0),
                        date('d.m.Y'),
                        date('H:i:s'),
                        $jtlVendorParams['tid']
                    );

                    $nextsubsdate = !empty($jtlVendorParams['next_subs_cycle'])
                                    ? $jtlVendorParams['next_subs_cycle']
                                    : $jtlVendorParams['paid_until'];

                    $callbackScriptText .= $novalnetOrderLanguage['__NN_callback_subscription_chargingdate'] . (!empty($nextsubsdate) ? date('d.m.Y H:i:s', strtotime($nextsubsdate)) : '');
                    // Completes the callback execution
                    $jtlVendorScript->callbackFinalProcess($callbackScriptText, $jtlVendorHistory);
                } elseif (in_array($jtlVendorParams['payment_type'], array('PAYPAL', 'PRZELEWY24'))) {
                    $query = $DB->$shopQuery('SELECT SUM(nCallbackAmount) AS kCallbackAmount FROM xplugin_novalnetag_tcallback WHERE cBestellnummer = "' . $orderId . '"', 1);

                    if ($query->kCallbackAmount <= $orderAmount) {
                        $callbackScriptText = PHP_EOL . sprintf(
                            $novalnetOrderLanguage['__NN_callback_initial_execution'],
                            $jtlVendorParams['shop_tid'],
                            gibPreisLocalizedOhneFaktor($jtlVendorParams['amount'] / 100, $currencyObj, 0),
                            date('d.m.Y'),
                            date('H:i:s'),
                            $jtlVendorParams['tid']
                        );
                        // Retrieves payment object from class PaymentMethod
                        $jtlPaymentmethod = PaymentMethod::create($jtlVendorHistory->kPaymentId);

                        $incomingPayment           = new stdClass();
                        $incomingPayment->fBetrag  = $orderAmount / 100;
                        $incomingPayment->cISO     = $jtlVendorHistory->cCurrencyISO;
                        $incomingPayment->cHinweis = $jtlVendorParams['shop_tid'];
                        $jtlPaymentmethod->name    = $jtlVendorHistory->cZahlungsartName;
                        // Adds the current transaction into the shop's order table
                        $jtlPaymentmethod->addIncomingPayment($jtlVendorHistory, $incomingPayment);

                        NovalnetGateway::performDbExecution('tbestellung', 'cAbgeholt="N", dBezahltDatum = now(), cStatus=' . constant($oPlugin->oPluginEinstellungAssoc_arr[$jtlVendorHistory->cPaymentType.'_set_order_status']), 'cBestellNr = "' . $orderId . '"'); // Updates the value into the database

                        NovalnetGateway::performDbExecution('xplugin_novalnetag_tnovalnet_status', 'nStatuswert = "' . $jtlVendorParams['tid_status'] . '"', 'cNnorderid="' . $orderId . '"'); // Updates the value into the database
                        // Completes the callback execution
                        $jtlVendorScript->callbackFinalProcess($callbackScriptText, $jtlVendorHistory);
                    }
                    $jtlVendorScript->displayMessage('Novalnet Callbackscript received. Order already Paid', $orderId);
                } elseif (in_array($jtlVendorParams['payment_type'], array('INVOICE_START','GUARANTEED_INVOICE', 'DIRECT_DEBIT_SEPA', 'GUARANTEED_DIRECT_DEBIT_SEPA'))) {
                    if(in_array($jtlVendorParams['tid_status'], array(91,99,100)) && in_array($jtlVendorHistory->cNnStatus, array('75','91','99'))){
                        $ordStatus = constant($oPlugin->oPluginEinstellungAssoc_arr['confirm_order_status']);
                        if($jtlVendorParams['tid_status'] == 100 && $jtlVendorHistory->cNnStatus == 91){
                            $transDetails = $DB->$shopQuery('SELECT cbankName, cbankCity, cbankIban, cbankBic, bTestmodus, cReferenceValues FROM xplugin_novalnetag_tpreinvoice_transaction_details WHERE cBestellnummer = "' . $orderId . '"', 1);
                            $configDb = unserialize($jtlVendorHistory->cKonfigurations);

                            if($jtlVendorHistory->cAdditionalInfo != '')
                                $accountHolder = unserialize($jtlVendorHistory->cAdditionalInfo);

                            $invoicePrepaymentDetails = array(
                                  'invoice_bankname'  => $transDetails->cbankName,
                                  'invoice_bankplace' => $transDetails->cbankCity,
                                  'amount'            => ($jtlVendorHistory->fGesamtsumme / 100),
                                  'currency'          => $jtlVendorHistory->cCurrencyISO,
                                  'tid'               => $jtlVendorParams['shop_tid'],
                                  'invoice_iban'      => $transDetails->cbankIban,
                                  'invoice_bic'       => $transDetails->cbankBic,
                                  'due_date'          => $jtlVendorParams['due_date'],
                                  'product_id'        => $jtlVendorParams['product_id'],
                                  'order_no'          => $orderId,
                                  'kSprache'          => $jtlVendorHistory->kSprache,
                                  'referenceValues'   => $transDetails->cReferenceValues,
                                  'invoice_type'      => 'INVOICE',
                                  'invoice_account_holder' => (isset($accountHolder['invoice_account_holder']) ? $accountHolder['invoice_account_holder'] : $jtlVendorParams['invoice_account_holder'])
                                );
                            $comments = $jtlVendorHistory->cZahlungsartName . PHP_EOL;
                            if(!empty($configDb['key']) && $configDb['key'] == '41') {
                                $comments .= $novalnetOrderLanguage['__NN_guarantee_payments_comments'] . PHP_EOL;
                            }
                                $comments .= $novalnetOrderLanguage['__NN_tid_label'] . $jtlVendorParams['shop_tid'] . PHP_EOL;

                                if (isset($jtlVendorParams['test_mode']) && $jtlVendorParams['test_mode'] == '1') { // Condition to retrieve the testmode for the payment
                                    $comments .= $novalnetOrderLanguage['__NN_test_order'] . PHP_EOL;
                                }

                                $comments .= $jtlPaymentClass->formInvoicePrepaymentComments(
                                    $invoicePrepaymentDetails,
                                    $jtlVendorHistory->cCurrencyISO,
                                    true
                                );

                                NovalnetGateway::performDbExecution('tbestellung', 'cKommentar = CONCAT(cKommentar, "' . PHP_EOL . PHP_EOL .$comments . '"), cAbgeholt="N"', 'cBestellNr = "' . $orderId . '"'); // Updates the value into the database

                            $sendOrderMail = true;
                            $callbackScriptText = sprintf(
                                $novalnetOrderLanguage['__NN_order_confirmation_text'],
                                date('d.m.Y'),
                                date('H:i:s')
                            );
                            NovalnetGateway::performDbExecution('xplugin_novalnetag_tpreinvoice_transaction_details', 'cRechnungDuedate = "' . $jtlVendorParams['due_date'] . '"', 'cBestellnummer ="' . $orderId . '"');

                        } elseif($jtlVendorParams['tid_status'] == 100 && $jtlVendorHistory->cNnStatus == 99){
                            $callbackScriptText = PHP_EOL . sprintf(
                                $novalnetOrderLanguage['__NN_order_confirmation_text'],
                                date('d.m.Y'),
                                date('H:i:s')
                            );
                            NovalnetGateway::performDbExecution('tbestellung', 'cAbgeholt="N"', 'cBestellNr = "' . $orderId . '"');
                        } elseif(in_array($jtlVendorParams['tid_status'], array(91,99,100)) && $jtlVendorHistory->cNnStatus == 75) {
                            $ordStatus = $jtlVendorParams['tid_status'] == 91 ? constant($oPlugin->oPluginEinstellungAssoc_arr[$jtlVendorHistory->cPaymentType.'_callback_status']) : constant($oPlugin->oPluginEinstellungAssoc_arr[$jtlVendorHistory->cPaymentType.'_set_order_status']);


                            // Retrieves payment object from class PaymentMethod
                           $jtlPaymentmethod = PaymentMethod::create($jtlVendorHistory->kPaymentId);

                           $incomingPayment           = new stdClass();
                           $incomingPayment->fBetrag  = $orderAmount / 100;
                           $incomingPayment->cISO     = $jtlVendorHistory->cCurrencyISO;
                           $incomingPayment->cHinweis = $jtlVendorParams['shop_tid'];
                           $jtlPaymentmethod->name    = $jtlVendorHistory->cZahlungsartName;
                           // Adds the current transaction into the shop's order table
                           $jtlPaymentmethod->addIncomingPayment($jtlVendorHistory, $incomingPayment);

                            if($jtlVendorHistory->cPaymentType == 'novalnet_invoice' && $jtlVendorParams['tid_status']==100){

                                    $sendOrderMail = true;

                                $ordStatus = constant($oPlugin->oPluginEinstellungAssoc_arr[$jtlVendorHistory->cPaymentType.'_callback_status']);

                                $transDetails = $DB->$shopQuery('SELECT cbankName, cbankCity, cbankIban, cbankBic, bTestmodus, cReferenceValues FROM xplugin_novalnetag_tpreinvoice_transaction_details WHERE cBestellnummer = "' . $orderId . '"', 1);

                                if($jtlVendorHistory->cAdditionalInfo != '')
                                    $accountHolder = unserialize($jtlVendorHistory->cAdditionalInfo);

                            $invoicePrepaymentDetails = array(
                                  'invoice_bankname'  => $transDetails->cbankName,
                                  'invoice_bankplace' => $transDetails->cbankCity,
                                  'amount'            => ($jtlVendorHistory->fGesamtsumme / 100),
                                  'currency'          => $jtlVendorHistory->cCurrencyISO,
                                  'tid'               => $jtlVendorParams['shop_tid'],
                                  'invoice_iban'      => $transDetails->cbankIban,
                                  'invoice_bic'       => $transDetails->cbankBic,
                                  'due_date'          => $jtlVendorParams['due_date'],
                                  'product_id'        => $jtlVendorParams['product_id'],
                                  'order_no'          => $orderId,
                                  'kSprache'          => $jtlVendorHistory->kSprache,
                                  'referenceValues'   => $transDetails->cReferenceValues,
                                  'invoice_type'      => 'INVOICE',
                                  'invoice_account_holder' => (isset($accountHolder['invoice_account_holder']) ? $accountHolder['invoice_account_holder'] : $jtlVendorParams['invoice_account_holder'])
                                );

                                $comments = $jtlVendorHistory->cZahlungsartName . PHP_EOL;
                                $comments .= $novalnetOrderLanguage['__NN_guarantee_payments_comments'] . PHP_EOL;
                                $comments .= $novalnetOrderLanguage['__NN_tid_label'] . $jtlVendorParams['shop_tid'] . PHP_EOL;

                                if (isset($jtlVendorParams['test_mode']) && $jtlVendorParams['test_mode'] == '1') { // Condition to retrieve the testmode for the payment
                                    $comments .= $novalnetOrderLanguage['__NN_test_order'] . PHP_EOL;
                                }

                                $comments .= $jtlPaymentClass->formInvoicePrepaymentComments(
                                    $invoicePrepaymentDetails,
                                    $jtlVendorHistory->cCurrencyISO,
                                    true
                                );

                                NovalnetGateway::performDbExecution('tbestellung', 'cKommentar = CONCAT(cKommentar, "' . PHP_EOL . PHP_EOL .$comments . '"), cAbgeholt="N"', 'cBestellNr = "' . $orderId . '"'); // Updates the value into the database

                            }
                            if(in_array($jtlVendorParams['tid_status'], array(91,99))){

                                $callbackScriptText = PHP_EOL . sprintf(
                                    $novalnetOrderLanguage['__NN_callback_pending_to_onhold_status_change'],
                                    $jtlVendorParams['shop_tid'],
                                    date('d.m.Y'),
                                    date('H:i:s')
                                );
                            } else {
                                $callbackScriptText = sprintf(
                                    $novalnetOrderLanguage['__NN_order_confirmation_text'],
                                    $jtlVendorParams['shop_tid'],
                                    date('d.m.Y'),
                                    date('H:i:s')
                                );
                                NovalnetGateway::performDbExecution('tbestellung', 'cAbgeholt="N"', 'cBestellNr = "' . $orderId . '"');
                            }
                        }

                        NovalnetGateway::performDbExecution('tbestellung', 'cStatus=' . $ordStatus, 'cBestellNr = "' . $orderId . '"'); // Updates the value into the database

                        NovalnetGateway::performDbExecution('xplugin_novalnetag_tnovalnet_status', 'nStatuswert = "' . $jtlVendorParams['tid_status'] . '"', 'cNnorderid="' . $orderId . '"');

                        if($sendOrderMail){
                            $jtlPaymentmethod = PaymentMethod::create($jtlVendorHistory->kPaymentId);
                            $jtlPaymentmethod->sendMail($jtlVendorHistory->kBestellung, MAILTEMPLATE_BESTELLUNG_AKTUALISIERT);
                        }
                         // Completes the callback execution
                        $jtlVendorScript->callbackFinalProcess($callbackScriptText, $jtlVendorHistory);
                    }
                }

                $error = 'Novalnet Callbackscript received. Payment type ( ' . $jtlVendorParams['payment_type'] . ' ) is not applicable for this process!';
                $jtlVendorScript->displayMessage($error,$orderId);
            } elseif (($jtlVendorParams['payment_type'] == 'PRZELEWY24'
                    && !in_array($jtlVendorParams['tid_status'], array(86, 100)))) {
                $cancelReason = $jtlPaymentClass->getResponseText($jtlVendorParams);

                $callbackScriptText = PHP_EOL . $novalnetOrderLanguage['__NN_callback_transaction_cancellation'] . $cancelReason;

                NovalnetGateway::performDbExecution('tbestellung', 'cStatus=' . constant($oPlugin->oPluginEinstellungAssoc_arr['cancel_order_status']), 'cBestellNr = "' . $orderId . '"'); // Updates the value into the database

                NovalnetGateway::performDbExecution('xplugin_novalnetag_tnovalnet_status', 'nStatuswert = "' . $jtlVendorParams['tid_status'] . '"', 'cNnorderid="' . $orderId . '"'); // Updates the value into the database
                // Completes the callback execution
                $jtlVendorScript->callbackFinalProcess($callbackScriptText, $jtlVendorHistory);
            } elseif ($jtlVendorParams['status'] != 100 && isset($jtlVendorParams['subs_billing'])) {
                $cancelReason = $jtlPaymentClass->getResponseText($jtlVendorParams);

                NovalnetGateway::performDbExecution('xplugin_novalnetag_tsubscription_details', 'cTerminationReason = "' .$cancelReason . '", dTerminationAt = "' . date('Y-m-d H:i:s') . '"', 'nTid="' . $jtlVendorParams['shop_tid'] . '"'); // Updates the value into the database

                $callbackScriptText = sprintf($novalnetOrderLanguage['__NN_callback_subscription_cancellation'], $jtlVendorParams['shop_tid'], date('d.m.Y'), date('H:i:s')) . PHP_EOL . $novalnetOrderLanguage['__NN_subscription_cancelled'] . $cancelReason;

                NovalnetGateway::performDbExecution('tbestellung', 'cStatus=' . constant($oPlugin->oPluginEinstellungAssoc_arr['subscription_order_status']), 'cBestellNr = "' . $orderId . '"'); // Updates the value into the database
                // Completes the callback execution
                $jtlVendorScript->callbackFinalProcess($callbackScriptText, $jtlVendorHistory);
            }
            break;
    }

    /*
    * Error section : When status executing other than 100
    */
    $jtlVendorScript->displayMessage('Novalnet callback received. Status is not valid', $orderId);
    }
    }

    /**
    * Class NovalnetVendorScript
    */
    class NovalnetVendorScript
    {

    /**
    * @Type of payments available - Level : 0
    * @var array
    */
    protected $aryPayments = array(
                            'CREDITCARD',
                            'INVOICE_START',
                            'GUARANTEED_INVOICE',
                            'DIRECT_DEBIT_SEPA',
                            'GUARANTEED_DIRECT_DEBIT_SEPA',
                            'ONLINE_TRANSFER',
                            'IDEAL',
                            'EPS',
                            'GIROPAY',
                            'PAYPAL',
                            'PRZELEWY24',
                            'CASHPAYMENT'
                        );

    /**
    * @Type of Chargebacks available - Level : 1
    * @var array
    */
    protected $aryChargebacks = array(
                                'RETURN_DEBIT_SEPA',
                                'REVERSAL',
                                'CREDITCARD_BOOKBACK',
                                'CREDITCARD_CHARGEBACK',
                                'REFUND_BY_BANK_TRANSFER_EU',
                                'PAYPAL_BOOKBACK',
                                'PRZELEWY24_REFUND',
                                'CASHPAYMENT_REFUND',
                                'GUARANTEED_INVOICE_BOOKBACK',
                                'GUARANTEED_SEPA_BOOKBACK'
                            );

    /**
    * @Type of CreditEntry payment and Collections available - Level : 2
    * @var array
    */
    protected $aryCollection = array(
                                'INVOICE_CREDIT',
                                'CREDIT_ENTRY_CREDITCARD',
                                'CREDIT_ENTRY_SEPA',
                                'DEBT_COLLECTION_SEPA',
                                'DEBT_COLLECTION_CREDITCARD',
                                'ONLINE_TRANSFER_CREDIT',
                                'CASHPAYMENT_CREDIT'
                            );

    /**
    * @var array
    */
    protected $callbackRequestParams = array();

    /**
    * @var array
    */
    protected $aPaymentTypes = array(
    'novalnet_invoice'      => array('INVOICE_START', 'INVOICE_CREDIT', 'GUARANTEED_INVOICE', 'SUBSCRIPTION_STOP', 'SUBSCRIPTION_REACTIVATE', 'REFUND_BY_BANK_TRANSFER_EU', 'GUARANTEED_INVOICE_BOOKBACK'),
    'novalnet_prepayment'   => array('INVOICE_START', 'INVOICE_CREDIT', 'SUBSCRIPTION_STOP', 'SUBSCRIPTION_REACTIVATE', 'REFUND_BY_BANK_TRANSFER_EU'),
    'novalnet_paypal'       => array('PAYPAL', 'SUBSCRIPTION_STOP', 'PAYPAL_BOOKBACK', 'SUBSCRIPTION_REACTIVATE'),
    'novalnet_banktransfer' => array(
                                    'ONLINE_TRANSFER',
                                    'REFUND_BY_BANK_TRANSFER_EU',
                                    'ONLINE_TRANSFER_CREDIT',
                                    'REVERSAL'
                                ),
    'novalnet_cc'           => array(
                                    'CREDITCARD',
                                    'CREDITCARD_BOOKBACK',
                                    'CREDITCARD_CHARGEBACK',
                                    'SUBSCRIPTION_STOP',
                                    'SUBSCRIPTION_REACTIVATE',
                                    'CREDIT_ENTRY_CREDITCARD',
                                    'DEBT_COLLECTION_CREDITCARD'
                                ),
    'novalnet_ideal'        => array('IDEAL', 'REFUND_BY_BANK_TRANSFER_EU', 'ONLINE_TRANSFER_CREDIT', 'REVERSAL'),
    'novalnet_sepa'         => array(
                                    'DIRECT_DEBIT_SEPA',
                                    'GUARANTEED_DIRECT_DEBIT_SEPA',
                                    'RETURN_DEBIT_SEPA',
                                    'SUBSCRIPTION_STOP',
                                    'SUBSCRIPTION_REACTIVATE',
                                    'REFUND_BY_BANK_TRANSFER_EU',
                                    'CREDIT_ENTRY_SEPA',
                                    'DEBT_COLLECTION_SEPA',
                                    'GUARANTEED_SEPA_BOOKBACK'
                                ),
    'novalnet_eps'          => array('EPS', 'REFUND_BY_BANK_TRANSFER_EU', 'ONLINE_TRANSFER_CREDIT'),
    'novalnet_giropay'      => array('GIROPAY', 'REFUND_BY_BANK_TRANSFER_EU', 'ONLINE_TRANSFER_CREDIT'),
    'novalnet_przelewy24'   => array('PRZELEWY24', 'PRZELEWY24_REFUND'),
    'novalnet_cashpayment'  => array('CASHPAYMENT', 'CASHPAYMENT_CREDIT', 'CASHPAYMENT_REFUND'),
    );

    /**
    * @var array
    */
    protected $paramsRequired = array();

    /**
    * @var array
    */
    protected $affParamsRequired = array();

    protected $notifyMail = 'technic@novalnet.de';

    /**
    *
    * Constructor
    *
    */
    public function __construct($aryCapture)
    {
    // Required mandatory callback params
    $this->paramsRequired = array('vendor_id', 'tid', 'payment_type', 'status', 'tid_status');

    // Required mandatory affiliate callback params
    $this->affParamsRequired = array(
                                    'vendor_id',
                                    'vendor_authcode',
                                    'aff_id',
                                    'aff_authcode',
                                    'aff_accesskey',
                                    'product_id'
                            );

    if (in_array($aryCapture['payment_type'], array('SUBSCRIPTION_STOP', 'SUBSCRIPTION_REACTIVATE')) || isset($aryCapture['subs_billing']) && $aryCapture['subs_billing'] == '1') {
        array_push($this->paramsRequired, 'signup_tid');
    } elseif (isset($aryCapture['payment_type'])
        && in_array($aryCapture['payment_type'], array_merge($this->aryChargebacks, $this->aryCollection))) {
        array_push($this->paramsRequired, 'tid_payment');
    }

    // Validates the callback params before processing
    $this->arycaptureparams = $this->validateCaptureParams($aryCapture);
    }

    /**
    * Throws callback script texts and errors
    *
    * @param string $errorMsg
    * @param bool   $forceDisplay
    * @return none
    */
    public function displayMessage($errorMsg, $orderNo = '')
    {
    echo !empty($orderNo) ? 'message='. utf8_encode(html_entity_decode($errorMsg)).'&order_no='.$orderNo : 'message='.utf8_encode(html_entity_decode($errorMsg));
    exit;
    }

    /**
    * Get the payment type level for the request payment
    *
    * @param none
    * @return integer/bool
    */
    public function getPaymentTypeLevel()
    {
    if (in_array($this->arycaptureparams['payment_type'], $this->aryPayments)) {
        return 0;
    } elseif (in_array($this->arycaptureparams['payment_type'], $this->aryChargebacks)) {
        return 1;
    } elseif (in_array($this->arycaptureparams['payment_type'], $this->aryCollection)) {
        return 2;
    }
    return 'none';
    }

    /**
    * Triggers mail notification to the mail address specified
    *
    * @param array $mailDatas
    * @return bool
    */
    public function sendMailNotification($mailDatas)
    {
    global $oPlugin, $DB, $shopQuery, $novalnetOrderLanguage;

    if (empty($novalnetOrderLanguage)) {
        $novalnetOrderLanguage = nnLoadOrderLanguage($oPlugin->kPlugin, $mailDatas['orderLanguage']);
    }

    $callbackBody = !empty($mailDatas['orderNo'])? $novalnetOrderLanguage['__NN_order_number_text'] . $mailDatas['orderNo'] . '<br/><br/>' . $mailDatas['comments'] : $mailDatas['comments'];

    if ($oPlugin->oPluginEmailvorlageAssoc_arr['novalnetcallbackmail']->cAktiv == 'Y') {
        $adminDetails = $DB->$shopQuery('SELECT cName, cMail from tadminlogin LIMIT 1', 1);

        $oMail                 = new stdClass();
        $oMail->cCallbackText  = $callbackBody;

        $oMail->mail = new stdClass();
        $oMail->mail->toName   = $adminDetails->cName;

        if (!empty($mailDatas['orderLanguage'])) {
            $oMail->tkunde = (!empty($mailDatas['customerNumber'])) ?
            new Kunde($mailDatas['customerNumber']) : new stdClass();
            $oMail->tkunde->kSprache = $mailDatas['orderLanguage'];
        }

        if (!empty($adminDetails->cMail)) {
            $oMail->mail->toEmail = $adminDetails->cMail;
        }
         // Triggers email notification regarding callback script execution
        sendeMail('kPlugin_' . $oPlugin->kPlugin . '_novalnetcallbackmail', $oMail);
    }

    if (!empty($mailDatas['exceededAmount'])) {
        $callbackBody = $callbackBody . 'Customer has paid more than the Order amount.';
    }

    $this->displayMessage($callbackBody);
    }

    /**
    * Validates the request parameters
    *
    * @param array $callbackRequestParams
    * @return array
    */
    public function validateCaptureParams($callbackRequestParams)
    {
    global $processTestMode;

    $realHostIp = gethostbyname('pay-nn.de');

    if (empty($realHostIp)) {
        $this->displayMessage('Novalnet HOST IP missing');
    }
     // Condition to check whether the callback is called from authorized IP
    if ((getRealIp() != $realHostIp) && !$processTestMode) {
        $this->displayMessage('Novalnet callback received. Unauthorised access from the IP ' . getRealIp());
    }

    if (!empty($callbackRequestParams['vendor_activation'])) {
        // Validates affiliate parameters
        $this->validateRequiredParameters($this->affParamsRequired, $callbackRequestParams);
    } else {
        // Validates basic callback parameters
        $this->validateRequiredParameters($this->paramsRequired, $callbackRequestParams);

        $tidCheck = array($callbackRequestParams['tid']);
        $callbackRequestParams['shop_tid'] = $callbackRequestParams['tid'];

        if (isset($callbackRequestParams['subs_billing'])
            && $callbackRequestParams['subs_billing'] != 1
            && in_array(
                $callbackRequestParams['payment_type'],
                array_merge($this->aryChargebacks, $this->aryCollection)
            )) {
            $tidCheck[] = $callbackRequestParams['tid_payment'];
            $callbackRequestParams['shop_tid'] = $callbackRequestParams['tid_payment'];
        }

        if (isset($callbackRequestParams['subs_billing'])
            && $callbackRequestParams['subs_billing'] == 1
            || $callbackRequestParams['payment_type'] == 'SUBSCRIPTION_STOP') {
            $tidCheck[] = !empty($callbackRequestParams['signup_tid']) ? $callbackRequestParams['signup_tid'] : '';
            $callbackRequestParams['shop_tid'] = !empty($callbackRequestParams['signup_tid'])
                                                    ? $callbackRequestParams['signup_tid']
                                                    : '';
        }

        foreach ($tidCheck as $arrTid) {
            if (!preg_match('/^[0-9]{17}$/', $arrTid)) {
                $this->displayMessage('Novalnet callback received. Invalid TID [' . $arrTid . '] for Order', $orderId);
            }
        }
    }
    return $callbackRequestParams;
    }

    /**
    * Get order details from the shop's database
    *
    * @param array $aryCaptureValues
    * @return object
    */
    public function getOrderByIncrementId($aryCaptureValues)
    {
    global $DB, $shopQuery;

    $order = $DB->$shopQuery('SELECT cNnorderid FROM xplugin_novalnetag_tnovalnet_status WHERE nNntid = "' . $aryCaptureValues['shop_tid'] . '"', 1);

    $uniqueOrderValue = !empty($order->cNnorderid) ? $order->cNnorderid  : '';

    $orderNo = !empty($aryCaptureValues['order_no']) ? $aryCaptureValues['order_no'] : '';

    if (empty($uniqueOrderValue)) {
        if ($orderNo) {
            $order = $DB->$shopQuery('SELECT kBestellung FROM tbestellung WHERE cBestellNr = "' .$orderNo. '"', 1);

            if (empty($order->kBestellung)) {
                $this->technicmail($aryCaptureValues, $orderNo);

                $this->displayMessage('Transaction mapping failed', $orderNo);
            }
             // Gets unique order ID for the particular order stored in shop database
            $uniqueOrderValue = $this->getUniqueOrderValue($orderNo);

            $order = new Bestellung($uniqueOrderValue);
            $this->addOrderObjectValues($order); // Adds up additional order object values for callback process

            $this->handleCommunicationBreak($order, $aryCaptureValues); // Handles communication failure scenario
        } else {
            $this->displayMessage('Transaction mapping failed', $orderNo);
        }
    }
    $uniqueOrder = $this->getUniqueOrderValue($uniqueOrderValue);
    $order = new Bestellung($uniqueOrder); // Loads order object from shop

    if (empty($order->kBestellung)) {
        $this->technicmail($aryCaptureValues, $orderNo);
        $this->displayMessage('Transaction mapping failed', $orderNo);
    }
    $this->addOrderObjectValues($order); // Adds up additional order object values for callback process

    if ($orderNo && $order->cBestellNr != $orderNo) {
        $this->displayMessage('Order number not valid', $orderNo);
    }

    if ((!array_key_exists($order->cPaymentType, $this->aPaymentTypes))
        || (!in_array($aryCaptureValues['payment_type'], $this->aPaymentTypes[$order->cPaymentType]) && $aryCaptureValues['payment_type'] != 'TRANSACTION_CANCELLATION')) {
        $this->displayMessage(
            'Novalnet callback received. Payment type [' .$aryCaptureValues['payment_type'] . '] is mismatched!');
    }

    return $order;
    }

    /**
    * Build the mail subject and message for the Novalnet Technic Team
    *
    * @param string $aryCaptureValues
    * @param string $orderNo
    */
    public function technicmail($aryCaptureValues, $orderNo)
    {
    $conf = Shop::getSettings(array(CONF_GLOBAL));
    $critical_mesaage = 'Dear Technic team,<br><br> Please evalute this transaction and contact our
                        payment module team at Novalnet.'.'<br>
                        Merchant ID: '.$aryCaptureValues['vendor_id'].'<br>'
                        .'Project ID: '.$aryCaptureValues['product_id'].'<br>'
                        .'TID: '.$aryCaptureValues['shop_tid'].'<br>'
                        .'TID status: '.$aryCaptureValues['tid_status'].'<br>'
                        .'Order no: '.$aryCaptureValues['order_no'].'<br>'
                        .'E-mail: '.$aryCaptureValues['email'].'<br><br>'
                        .'Regards,<br>
                        Novalnet Team';
    $mail = new stdClass();
    $mail->fromName     = $conf['global']['global_shopname'];
    $mail->fromEmail    = $aryCaptureValues['email'];//shop mail
    $mail->fromEmail    = $aryCaptureValues['email'];
    $mail->toEmail      = $this->notifyMail;
    $mail->subject      = 'Critical error on shop system '.$conf['global']['global_shopname'].': order not found for TID:'.$aryCaptureValues['shop_tid'];
    $mail->bodyText     = '';
    $mail->bodyHtml     = $critical_mesaage;

    verschickeMail($mail); //  Triggers email notification  for technic
    }

    /**
    * To get order object's kBestellung value
    *
    * @param string $orderNo
    * @return integer
    */
    public function getUniqueOrderValue($orderNo)
    {
    global $DB, $shopQuery;

    $uniqueValue = $DB->$shopQuery('SELECT kBestellung FROM tbestellung WHERE cBestellNr = "' . $orderNo . '"', 1);

    return $uniqueValue->kBestellung;
    }

    /**
    * To check the required parameters is present or not
    *
    * @param array $paramsRequired
    * @param array $callbackRequestParams
    * @return bool
    */
    public function validateRequiredParameters($paramsRequired, $callbackRequestParams)
    {
    foreach ($paramsRequired as $k => $v) {
        if (empty($callbackRequestParams[$v])) {
            $this->displayMessage('Required param (' . $v . ') missing!');
        }
    }
    }

    /**
    * Handling communication breakup
    *
    * @param array $order
    * @param array $callbackRequestParams
    * @return none
    */
    public function handleCommunicationBreak($order, $callbackRequestParams)
    {
    global $oPlugin, $jtlPaymentClass, $DB, $shopQuery;

    // Retrieves the language variables based on the end-user's order language
    $novalnetOrderLanguage = nnLoadOrderLanguage($oPlugin->kPlugin, $order->kSprache);

    $orderId = $order->cBestellNr;

    $transactionComments = $order->cZahlungsartName . PHP_EOL;
    $transactionComments.= $novalnetOrderLanguage['__NN_tid_label'] . $callbackRequestParams['shop_tid']  . PHP_EOL;

    if (!empty($callbackRequestParams['test_mode'])) {
        $transactionComments .= $novalnetOrderLanguage['__NN_test_order'] . PHP_EOL;
    }
     // Retrieves payment object from class PaymentMethod
    $jtlPaymentmethod = PaymentMethod::create($order->kPaymentId);

    if ($callbackRequestParams['status'] == 100 && in_array($callbackRequestParams['tid_status'], array(75, 85, 86, 90, 91, 98, 99, 100))) { // Condition to check communication failure for the payment success
        $incomingPayment           = new stdClass();
        $incomingPayment->fBetrag  = $order->fGesamtsumme / 100;
        $incomingPayment->cISO     = $order->cCurrencyISO;
        $incomingPayment->cHinweis = $callbackRequestParams['shop_tid'];
        $jtlPaymentmethod->name    = $order->cZahlungsartName;
        // Adds the current transaction into the shop's order table
        $jtlPaymentmethod->addIncomingPayment($order, $incomingPayment);

        if (in_array($callbackRequestParams['tid_status'], array(86, 90))) {
            NovalnetGateway::performDbExecution('tbestellung', 'dBezahltDatum = now(), cAbgeholt="Y",
            cStatus=' . constant($oPlugin->oPluginEinstellungAssoc_arr[$order->cPaymentType . '_pending_status']), 'cBestellNr = "' . $orderId . '"'); // Updates the value into the database
        } else {
            NovalnetGateway::performDbExecution('tbestellung', 'dBezahltDatum = now(), cAbgeholt="N", cStatus=' . constant($oPlugin->oPluginEinstellungAssoc_arr[$order->cPaymentType.'_set_order_status']), 'cBestellNr = "' . $orderId . '"'); // Updates the value into the database
        }

        if ($oPlugin->oPluginEinstellungAssoc_arr['display_order_comments'] == '1') {
            NovalnetGateway::performDbExecution('tbestellung', 'cKommentar = CONCAT(cKommentar, "' . $transactionComments . '")', 'cBestellNr = "' . $orderId . '"'); // Updates the value into the database
        }

    } else { // Condition to check communication failure for the payment error

        // Article restock
        $orderDetails = $DB->$shopQuery('SELECT kArtikel, cArtNr, nAnzahl FROM twarenkorbpos WHERE nPosTyp = 1 AND kWarenkorb = "' . $order->kWarenkorb . '"',2);
        foreach($orderDetails as $orderArticles) {
            $kArtikel = $DB->$shopQuery('SELECT kArtikel, fLagerbestand FROM tartikel WHERE kArtikel = "' . $orderArticles->kArtikel . '"',2);
            foreach ($kArtikel as $kArtikelDeatails) {
              if ($kArtikelDeatails->kArtikel == $orderArticles->kArtikel)   {
                  $fLagerbestand = $orderArticles->nAnzahl + $kArtikelDeatails->fLagerbestand;
                NovalnetGateway::performDbExecution('tartikel', 'fLagerbestand = "'.$fLagerbestand.'"', 'cArtNr ="'.$orderArticles->cArtNr.'"');
              }
            }
        }
        if ($oPlugin->oPluginEinstellungAssoc_arr['display_order_comments'] == '1') {

            $transactionComments .=  $jtlPaymentClass->getResponseText($callbackRequestParams);

            NovalnetGateway::performDbExecution('tbestellung', 'cKommentar = CONCAT(cKommentar, "' . $transactionComments . '"), cAbgeholt="Y", cStatus=' . constant($oPlugin->oPluginEinstellungAssoc_arr['cancel_order_status']), 'cBestellNr = "' .$orderId . '"'); // Updates the value into the database
        } else {
            NovalnetGateway::performDbExecution('tbestellung', 'cAbgeholt="Y", cStatus=' . constant($oPlugin->oPluginEinstellungAssoc_arr['cancel_order_status']), 'cBestellNr = "' .$orderId . '"'); // Updates the value into the database
        }
    }

    $order->fGesamtsumme = $order->fGesamtsumme / 100; // As it will be converted already in the gateway file

    $jtlPaymentClass->insertOrderIntoDBForFailure(array('tid' => $callbackRequestParams['shop_tid'], 'payment' => $order->cPaymentType, 'email' => $callbackRequestParams['email'], 'status' => $callbackRequestParams['tid_status']), $order, $order->cPaymentType); // Insert the order details into Novalnet table

    if ($oPlugin->oPluginEinstellungAssoc_arr['vendorid'] != $callbackRequestParams['vendor_id']) {
        $insertAffiliate              = new stdClass();
        $insertAffiliate->nAffId      = $callbackRequestParams['vendor_id'];
        $insertAffiliate->cCustomerId = $order->kKunde;
        $insertAffiliate->nAffOrderNo = $order->cBestellNr;
        $DB->insertRow('xplugin_novalnetag_taff_user_detail', $insertAffiliate);
    }

    $jtlPaymentmethod->sendMail($order->kBestellung, MAILTEMPLATE_BESTELLUNG_AKTUALISIERT);

    $callbackScriptText = html_entity_decode($order->cZahlungsartName) . ' payment status updated';

    $this->callbackFinalProcess($callbackScriptText, $order); // Completes the callback execution
    }

    /**
    * Update affiliate process details into the Novalnet table for reference
    *
    * @param array $datas
    * @return bool
    */
    public function updateAffiliateDatas($datas)
    {
    global $DB;

    $insertAffiliate                  = new stdClass();
    $insertAffiliate->nVendorId       = $datas['vendor_id'];
    $insertAffiliate->cVendorAuthcode = $datas['vendor_authcode'];
    $insertAffiliate->nProductId      = $datas['product_id'];
    $insertAffiliate->cProductUrl     = $datas['product_url'];
    $insertAffiliate->dActivationDate = !empty($datas['activation_date'])
                                        ? $datas['activation_date']
                                        : date('d.m.Y H:i:s');
    $insertAffiliate->nAffId          = $datas['aff_id'];
    $insertAffiliate->cAffAuthcode    = $datas['aff_authcode'];
    $insertAffiliate->cAffAccesskey   = $datas['aff_accesskey'];

    $DB->insertRow('xplugin_novalnetag_taffiliate_account_detail', $insertAffiliate);
    }

    /**
    * Performs final callback process
    *
    * @param array   $callbackScriptText
    * @param array   $orderReference
    * @param boolean $greaterAmount
    * @return none
    */
    public function callbackFinalProcess($callbackScriptText, $orderReference, $greaterAmount = false)
    {
    // Update callback comments in Novalnet table
    NovalnetGateway::performDbExecution('tbestellung', 'cKommentar = CONCAT(cKommentar, "' . PHP_EOL . $callbackScriptText . '")', 'cBestellNr = "' . $orderReference->cBestellNr . '"'); // Updates the value into the database

    // Log callback process (for all types of payments default)
    $this->logCallbackProcess($orderReference);

    //Send notification mail to Merchant
    $this->sendMailNotification(array(
        'comments'       => $callbackScriptText,
        'exceededAmount' => $greaterAmount,
        'orderNo'        => $orderReference->cBestellNr,
        'orderLanguage'  => $orderReference->kSprache,
        'customerNumber' => $orderReference->kKunde
    ));
    }

    /**
    * To log callback process into the callback table
    *
    * @param  object $orderReference
    * @return bool
    */
    public function logCallbackProcess($orderReference)
    {
    global $DB, $shopQuery;

    $orderAmount = $DB->$shopQuery('SELECT fGesamtsumme FROM tbestellung WHERE cBestellNr = "' . $orderReference->cBestellNr . '"', 1);

    $paymentTypeLevel = $this->getPaymentTypeLevel();

    if (in_array($orderReference->cPaymentType, array('novalnet_paypal', 'novalnet_przelewy24'))
        && $this->arycaptureparams['tid_status'] == 100) {
        $amount = $orderAmount->fGesamtsumme * 100;
    } elseif ($paymentTypeLevel == 2) {
        $amount = $this->arycaptureparams['amount'];
    } else {
        $amount = 0;
    }

    $insertCallback                  = new stdClass();
    $insertCallback->dDatum          = date('Y-m-d H:i:s');
    $insertCallback->cZahlungsart    = $this->arycaptureparams['payment_type'];
    $insertCallback->nReferenzTid    = $this->arycaptureparams['tid'];
    $insertCallback->nCallbackTid    = $this->arycaptureparams['shop_tid'];
    $insertCallback->nCallbackAmount = $amount;
    $insertCallback->cWaehrung       = $orderReference->cCurrencyISO;
    $insertCallback->cBestellnummer  = $orderReference->cBestellNr;

    $DB->insertRow('xplugin_novalnetag_tcallback', $insertCallback);
    }

    /**
    * Retrieve the payment method for the order
    *
    * @param string $paymentId
    * @return string
    */
    public function getPaymentMethod($paymentId)
    {
    $paymentMethods = array(
                            'novalnetkaufaufrechnung' => 'novalnet_invoice',
                            'novalnetvorauskasse' => 'novalnet_prepayment',
                            'novalnetpaypal' => 'novalnet_paypal',
                            'novalnetkreditkarte' => 'novalnet_cc',
                            'novalnetsofort' => 'novalnet_banktransfer',
                            'novalnetideal' => 'novalnet_ideal',
                            'novalneteps' => 'novalnet_eps',
                            'novalnetlastschriftsepa' => 'novalnet_sepa',
                            'novalnetgiropay' => 'novalnet_giropay',
                            'novalnetprzelewy24' => 'novalnet_przelewy24',
                            'novalnetbarzahlen' => 'novalnet_cashpayment'
                    );

    foreach ($paymentMethods as $key => $value) {
        if (strpos($paymentId, $key)) {
            return $value;
        }
    }
    }

    /**
    * Adds up additional order object values for callback process
    *
    * @param object $order
    * @return none
    */
    public function addOrderObjectValues(&$order)
    {
    global $DB, $shopQuery;

    $orderValues = $DB->$shopQuery('SELECT nBetrag, nStatuswert, cKonfigurations, cAdditionalInfo FROM xplugin_novalnetag_tnovalnet_status WHERE nNntid = "' . $this->arycaptureparams['shop_tid'] . '"', 1);

    $order->kPaymentId   = nnGetPaymentModuleId($order->kZahlungsart);
    $order->cPaymentType = $this->getPaymentMethod($order->kPaymentId);
    $order->fGesamtsumme = !empty($orderValues->nBetrag)
                            ? $orderValues->nBetrag
                            : gibPreisString($order->fGesamtsumme) * 100;
    $order->cNnStatus    = !empty($orderValues->nStatuswert) ? $orderValues->nStatuswert : '';
    // Retrieves payment currency for request
    $order->cCurrencyISO = NovalnetGateway::getPaymentCurrency($order->cBestellNr);
    $order->cAdditionalInfo = $orderValues->cAdditionalInfo;
    }
    }
