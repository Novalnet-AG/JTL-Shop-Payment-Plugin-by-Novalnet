<?php
/**
 * Novalnet payment plugin
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Novalnet End User License Agreement
 *
 * DISCLAIMER
 *
 * If you wish to customize Novalnet payment extension for your needs,
 * please contact technic@novalnet.de for more information.
 *
 * @author  	Novalnet AG
 * @copyright  	Copyright (c) Novalnet
 * @license    	https://www.novalnet.de/payment-plugins/kostenlos/lizenz
 *
 * Script: Novalnet.extensions.php
 *
 */
header('Access-Control-Allow-Origin: *');

// Request
$request = $_REQUEST;

require_once($request['pluginInc']);
// Get plugin object
$oPlugin = Plugin::getPluginById('novalnetag');

require_once PFAD_ROOT . PFAD_PLUGIN . $oPlugin->cPluginID . '/' . PFAD_PLUGIN_VERSION . $oPlugin->nVersion . '/' . PFAD_PLUGIN_PAYMENTMETHOD  . PFAD_CLASSES .'NovalnetGateway.class.php';
require_once PFAD_ROOT . PFAD_INCLUDES_MODULES . 'PaymentMethod.class.php';
require_once PFAD_ROOT . PFAD_CLASSES . 'class.JTL-Shop.Bestellung.php';
require_once PFAD_ROOT . PFAD_INCLUDES . 'mailTools.php';

global $DB, $shopQuery, $selectQuery;

$sendOrderMail = false;

$orderNo = $request['orderNo'];

$currency = NovalnetGateway::getPaymentCurrency($orderNo); // Get currency type for the current order

// Get order details
$orderDetails  = $DB->$shopQuery('SELECT cKonfigurations, cZahlungsmethode, nNntid, nBetrag, cAdditionalInfo, cZeroBookingParams FROM xplugin_novalnetag_tnovalnet_status WHERE cNnorderid = "' . $orderNo . '"', 1);

$paymentMethod = $orderDetails->cZahlungsmethode; // Payment method for the order

// Get invoice details
if (in_array($paymentMethod, array('novalnet_invoice', 'novalnet_prepayment'))) {
    $transDetails = $DB->$shopQuery('SELECT cbankName, cbankCity, cbankIban, cbankBic, bTestmodus, cReferenceValues FROM xplugin_novalnetag_tpreinvoice_transaction_details WHERE cBestellnummer = "' . $orderNo . '"', 1);
}

// Get order reference number
$orderRef = $DB->$shopQuery('SELECT kBestellung FROM tbestellung WHERE cBestellNr = "' . $orderNo . '"', 1);

// Get Novalnet gateway class instance
$novalnetGateway = NovalnetGateway::getInstance();

// New order reference
$orderObj = new Bestellung($orderRef->kBestellung);
$orderObj->payment_id = nnGetPaymentModuleId($orderObj->kZahlungsart);

$configDb = unserialize($orderDetails->cKonfigurations);

$extensionServerRequest = array();

$currencyObj = $DB->$selectQuery(
    'twaehrung',
    'kWaehrung',
    $orderObj->kWaehrung
);

if ($request['apiStatus'] == 'zeroBooking') {
    $extensionServerRequest = unserialize($orderDetails->cZeroBookingParams);
    $extensionServerRequest['amount'] = $request['bookAmount'];
    $extensionServerRequest['payment_ref'] = $orderDetails->nNntid;
    $extensionServerRequest['remote_ip']   = nnGetIpAddress('REMOTE_ADDR');
    // If the payment method is Novalnet SEPA, fetch duedate for transaction booking
    if ($paymentMethod == 'novalnet_sepa') {
        require_once(PFAD_ROOT . PFAD_PLUGIN . $oPlugin->cPluginID . '/' . PFAD_PLUGIN_VERSION . $oPlugin->nVersion . '/' . PFAD_PLUGIN_PAYMENTMETHOD  . PFAD_CLASSES . 'NovalnetSepa.class.php');

        $extensionServerRequest['sepa_due_date'] = NovalnetSepa::getSepaDuedate(trim($oPlugin->oPluginEinstellungAssoc_arr['sepa_due_date']));
    }
} else {
    $extensionServerRequest = array(
        'vendor'    => $configDb['vendor'],
        'auth_code' => $configDb['auth_code'],
        'product'   => $configDb['product'],
        'tariff'    => $configDb['tariff'],
        'key'       => !empty($configDb['key']) ? $configDb['key'] : nnGetPaymentKey($paymentMethod),
        'tid'       => $orderDetails->nNntid,
        'remote_ip' => nnGetIpAddress('REMOTE_ADDR')
    );

    if ($request['apiStatus'] == 'refund') { // Additional parameters for transaction refund request
        $extensionServerRequest['refund_request'] = 1;
        $extensionServerRequest['refund_param']   = $request['refundAmount'];

        if (!empty($request['refundRef'])) {
            $extensionServerRequest['refund_ref'] = $request['refundRef'];
        }
    }  else {
        $extensionServerRequest['status']        = 100;
        $extensionServerRequest['edit_status']   = 1;

        if ($request['apiStatus'] == 'amountUpdate') { // Additional parameters for transaction amount update request
            $extensionServerRequest['update_inv_amount'] = 1;
            $extensionServerRequest['amount']            = $request['amount'];

            if (!empty($request['dueDateChange'])) { // Additional parameter for due date update request
                $extensionServerRequest['due_date'] = $request['dueDateChange'];
            }
        } elseif ($request['apiStatus'] == 'void') {
            $extensionServerRequest['status'] = 103;
        }
    }
}

if (!empty($extensionServerRequest)) {
    // Retrieves the language variables based on the end-user's order language
    $novalnetOrderLanguage = nnLoadOrderLanguage($oPlugin->kPlugin, $orderObj->kSprache);
    // Api extension call to server
    $extensionResponse = http_get_contents('https://payport.novalnet.de/paygate.jsp', $novalnetGateway->getGatewayTimeout(), $extensionServerRequest); // Core function - Make curl request call
    parse_str($extensionResponse, $transactionResponse);

    if ($request['apiStatus'] == 'zeroBooking') {
        $extensionServerRequest['tid'] = $transactionResponse['tid'];
    }

    if ($transactionResponse['status'] == 100) {  // Extension handling on success
        switch ($request['apiStatus']) {
            case 'capture':
            case 'void':
                if ($request['apiStatus'] == 'capture') {
                    if($paymentMethod == 'novalnet_invoice')
                    {
                    if($orderDetails->cAdditionalInfo != '')
                        $accountHolder = unserialize($orderDetails->cAdditionalInfo);
                    
                    $invoicePrepaymentDetails = array(
                      'invoice_bankname'  => $transDetails->cbankName,
                      'invoice_bankplace' => $transDetails->cbankCity,
                      'amount'            => $orderObj->fGesamtsumme ,
                      'currency'          => $currency,
                      'tid'               => $orderDetails->nNntid,
                      'invoice_iban'      => $transDetails->cbankIban,
                      'invoice_bic'       => $transDetails->cbankBic,
                      'due_date'          => $transactionResponse['due_date'],
                      'product_id'        => $configDb['product'],
                      'order_no'          => $orderNo,
                      'kSprache'          => $orderObj->kSprache,
                      'referenceValues'   => $transDetails->cReferenceValues,
                      'invoice_type'      => $paymentMethod == 'novalnet_prepayment' ? 'PREPAYMENT' : 'INVOICE',
                      'invoice_account_holder' => (isset($accountHolder['invoice_account_holder'])) ? $accountHolder['invoice_account_holder'] : ''
                    );

                    $comment = $novalnetGateway->formInvoicePrepaymentComments(
                        $invoicePrepaymentDetails,
                        $currency,
                        true
                    );
                      
                      $sendOrderMail = true;
            
                    }
                    
                    $transactionResponseMessage = sprintf(
                        $novalnetOrderLanguage['__NN_transaction_capture_text'],
                        date('d.m.Y'),
                        date('H:i:s')
                    );

                    $status = $oPlugin->oPluginEinstellungAssoc_arr['confirm_order_status'];

                    $paidUpdate = '';

                    if (!in_array($paymentMethod, array('novalnet_invoice', 'novalnet_prepayment')) || ($paymentMethod == 'novalnet_paypal' && $transactionResponse['tid_status'] == 100)) { // Pending payments will be added to incoming transactions
                        if ($paymentMethod == 'novalnet_paypal') {
                            $insertCallback = new stdClass();
                            $insertCallback->cBestellnummer  = $orderNo;
                            $insertCallback->dDatum          = date('Y-m-d H:i:s');
                            $insertCallback->cZahlungsart    = 'PAYPAL';
                            $insertCallback->nReferenzTid    = $extensionServerRequest['tid'];
                            $insertCallback->nCallbackAmount = $orderObj->fGesamtsumme * 100;
                            $insertCallback->cWaehrung       = $currency;

                            $DB->insertRow('xplugin_novalnetag_tcallback', $insertCallback);

                            if ($orderDetails->cAdditionalInfo != '') {
                                $paypalTxnDetails = unserialize($orderDetails->cAdditionalInfo);
                                $paypalTxnDetails['referenceOption1'] = !empty($transactionResponse['paypal_transaction_id']) ?$transactionResponse['paypal_transaction_id'] : '';

                                NovalnetGateway::performDbExecution('xplugin_novalnetag_tnovalnet_status', "cAdditionalInfo= '". serialize($paypalTxnDetails)  . "'", 'cNnorderid = "' . $orderNo . '"'); // Updates the value into the database
                            }
                        }

                        $jtlPaymentmethod = PaymentMethod::create($orderObj->payment_id);

                        $incomingPayment           = new stdClass();
                        $incomingPayment->fBetrag  = $orderObj->fGesamtsumme;
                        $incomingPayment->cISO     = $currency;
                        $incomingPayment->cHinweis = $extensionServerRequest['tid'];
                        $jtlPaymentmethod->name    = $orderObj->cZahlungsartName;
                         // Adds the current transaction into the shop's order table
                        $jtlPaymentmethod->addIncomingPayment($orderObj, $incomingPayment);

                        $paidUpdate = ', dBezahltDatum = now()';
                    } elseif (in_array($paymentMethod, array('novalnet_invoice', 'novalnet_prepayment'))) {
                        if ($transactionResponse['due_date']) {
                            $transactionResponseMessage = sprintf(
                                $novalnetOrderLanguage['__NN_transaction_capture_duedate_text'],
                                $extensionServerRequest['tid'],
                                $transactionResponse['due_date']
                            );

                            NovalnetGateway::performDbExecution('xplugin_novalnetag_tpreinvoice_transaction_details', 'cRechnungDuedate = "' . $transactionResponse['due_date'] . '"', 'cBestellnummer ="' . $orderNo . '"');
                        }
                    }

                    NovalnetGateway::performDbExecution('tbestellung', 'cAbgeholt="N"' . $paidUpdate, 'cBestellNr = "' .$orderObj->cBestellNr . '"'); // Updates the value into the database
                } else {
                    $transactionResponseMessage = sprintf(
                        $novalnetOrderLanguage['__NN_transaction_void_text'],
                        date('d.m.Y'),
                        date('H:i:s')
                    );
                    $status = $oPlugin->oPluginEinstellungAssoc_arr['cancel_order_status'];
                }

                NovalnetGateway::performDbExecution('xplugin_novalnetag_tnovalnet_status', 'nStatuswert= ' . $transactionResponse['tid_status'], 'cNnorderid = "' . $orderNo . '"'); // Updates the value into the database

                NovalnetGateway::performDbExecution('tbestellung', 'cStatus= ' . constant($status), 'cBestellNr = "' . $orderNo . '"'); // Updates the value into the database

                break;

            case 'amountUpdate':
                if (!in_array($paymentMethod, array('novalnet_sepa', 'novalnet_cashpayment'))) {
                    NovalnetGateway::performDbExecution('xplugin_novalnetag_tpreinvoice_transaction_details', 'cRechnungDuedate = "' . $extensionServerRequest['due_date'] . '"', 'cBestellnummer ="' . $orderNo . '"');
                    if($orderDetails->cAdditionalInfo != '')
                        $accountHolder = unserialize($orderDetails->cAdditionalInfo);

                    $invoicePrepaymentDetails = array(
                      'invoice_bankname'  => $transDetails->cbankName,
                      'invoice_bankplace' => $transDetails->cbankCity,
                      'amount'            => ($extensionServerRequest['amount'] / 100),
                      'currency'          => $currency,
                      'tid'               => $orderDetails->nNntid,
                      'invoice_iban'      => $transDetails->cbankIban,
                      'invoice_bic'       => $transDetails->cbankBic,
                      'due_date'          => $extensionServerRequest['due_date'],
                      'product_id'        => $configDb['product'],
                      'order_no'          => $orderNo,
                      'kSprache'          => $orderObj->kSprache,
                      'referenceValues'   => $transDetails->cReferenceValues,
                      'invoice_type'      => $paymentMethod == 'novalnet_prepayment' ? 'PREPAYMENT' : 'INVOICE',
                      'invoice_account_holder' => (isset($accountHolder['invoice_account_holder'])) ? $accountHolder['invoice_account_holder'] : ''
                    );

                    $comments = $novalnetGateway->formInvoicePrepaymentComments(
                        $invoicePrepaymentDetails,
                        $currency,
                        true
                    );
                }

                if ($paymentMethod == 'novalnet_sepa') {
                    NovalnetGateway::performDbExecution('xplugin_novalnetag_tcallback', 'nCallbackAmount =' . $extensionServerRequest['amount'], 'cBestellnummer ="' . $orderNo . '"'); // Updates the value into the database

                    $transactionResponseMessage = sprintf(
                        $novalnetOrderLanguage['__NN_amount_update_message'],
                        gibPreisLocalizedOhneFaktor($extensionServerRequest['amount'] / 100, $currencyObj, 0),
                        date('d.m.Y'),
                        date('H:i:s')
                    );
                } else {
                    $updatedDate = new DateTime($extensionServerRequest['due_date']);

                    $transactionResponseMessage = sprintf(
                        $novalnetOrderLanguage['__NN_amount_duedate_update_message'],
                        gibPreisLocalizedOhneFaktor($extensionServerRequest['amount'] / 100, $currencyObj, 0),
                        $updatedDate->format('d.m.Y')
                    );
                }

                // Updates the value into the database
                if ($paymentMethod ==  'novalnet_cashpayment') {
                    $cashpaymentInfo = unserialize($orderDetails->cAdditionalInfo);
                    $convertedDate = date('d.m.Y', strtotime($cashpaymentInfo['cashpaymentSlipduedate']));

                    // Set up store comments
                    if ($oPlugin->oPluginEinstellungAssoc_arr['display_order_comments'] == '1') {
                        $comments = preg_replace('/'.$convertedDate.'/', $updatedDate->format('d.m.Y'), $orderObj->cKommentar, 1);
                    }

                    $updatedStoreComments = str_replace(
                        $convertedDate,
                        $updatedDate->format('d.m.Y'),
                        $cashpaymentInfo['cashpaymentStoreDetails']
                    );

                    $updatedCashdetails = serialize(array(
                        'cashpaymentSlipduedate'  => $extensionServerRequest['due_date'],
                        'cashpaymentStoreDetails' => $updatedStoreComments,
                    ));

                    NovalnetGateway::performDbExecution('xplugin_novalnetag_tnovalnet_status', "nStatuswert ='" . $transactionResponse['tid_status'] . "', nBetrag = '" . $extensionServerRequest['amount'] . "', cAdditionalInfo = '". $updatedCashdetails . "'", 'cNnorderid = "' . $orderNo . '"');
                } else {
                    NovalnetGateway::performDbExecution('xplugin_novalnetag_tnovalnet_status', 'nStatuswert ="' . $transactionResponse['tid_status'] . '", nBetrag = "' . $extensionServerRequest['amount'] . '"', 'cNnorderid = "' . $orderNo . '"');
                }

                break;
            
            case 'zeroBooking':
                $transactionResponseMessage = sprintf(
                    $novalnetOrderLanguage['__NN_zero_booking_message'],
                    (gibPreisLocalizedOhneFaktor($transactionResponse['amount'], $currencyObj, 0) . ' ' . $currency),
                    $extensionServerRequest['tid']
                );
                 // Pending payments will be added to incoming transactions only when booked
                if ($transactionResponse['tid_status'] == 100) {
                    $jtlPaymentmethod = PaymentMethod::create($orderObj->payment_id);

                    $incomingPayment           = new stdClass();
                    $incomingPayment->fBetrag  = $orderObj->fGesamtsumme;
                    $incomingPayment->cISO     = $currency;
                    $incomingPayment->cHinweis = $transactionResponse['tid'];
                    $jtlPaymentmethod->name    = $orderObj->cZahlungsartName;
                     // Adds the current transaction into the shop's order table
                    $jtlPaymentmethod->addIncomingPayment($orderObj, $incomingPayment);

                    NovalnetGateway::performDbExecution('tbestellung', 'dBezahltDatum = now()', 'cBestellNr = "' . $orderNo . '"'); // Updates the value into the database
                }

                NovalnetGateway::performDbExecution('xplugin_novalnetag_tnovalnet_status', 'nStatuswert= ' . $transactionResponse['tid_status'] . ', nNntid = ' . $transactionResponse['tid'] . ', nBetrag =' . ( $transactionResponse['amount'] * 100 ), 'cNnorderid = "' . $orderNo . '"'); // Updates the value into the database

                NovalnetGateway::performDbExecution('xplugin_novalnetag_tcallback', 'nCallbackAmount =' . ( $transactionResponse['amount'] * 100), 'cBestellnummer ="' . $orderNo . '"'); // Updates the value into the database

                break;

            default:
                $transactionResponseMessage = sprintf(
                    $novalnetOrderLanguage['__NN_refund_message'],
                    $extensionServerRequest['tid'],
                    gibPreisLocalizedOhneFaktor($request['refundAmount'] / 100, $currencyObj, 0)
                );

                if ($extensionServerRequest['key'] == 34 && !empty($transactionResponse['paypal_refund_tid'])) {
                    $transactionResponseMessage .= ' - PayPal Ref: ' . $transactionResponse['paypal_refund_tid']. '.';
                }

                if (!empty($transactionResponse['tid'])) {
                    $transactionResponseMessage .= $novalnetOrderLanguage['__NN_new_TID_message'] . $transactionResponse['tid'];
                }

                if ($transactionResponse['tid_status'] == 103) {
                    NovalnetGateway::performDbExecution('tbestellung', 'cStatus= ' . constant($oPlugin->oPluginEinstellungAssoc_arr['cancel_order_status']), 'cBestellNr = "' . $orderNo . '"'); // Updates the value into the database
                }

                NovalnetGateway::performDbExecution('xplugin_novalnetag_tnovalnet_status', 'nStatuswert= ' . $transactionResponse['tid_status'], 'cNnorderid = "' . $orderNo . '"'); // Updates the value into the database

                break;
        }
    } else { // Extension handling on error
        // Response for the api call
        $apiResponse = $novalnetGateway->getResponseText($transactionResponse);

        $transactionResponseMessage = in_array($request['apiStatus'], array('capture', 'void'))
                                        ? $apiResponse . '( Status : '. $transactionResponse['status'] .')'
                                        : $apiResponse;
    }
    if ($oPlugin->oPluginEinstellungAssoc_arr['display_order_comments'] == '1' && empty($apiResponse)) {
        if ($paymentMethod ==  'novalnet_cashpayment') {
            NovalnetGateway::performDbExecution('tbestellung', 'cKommentar = CONCAT("' . (!empty($comments) ?  $comments : '') . '", "' . PHP_EOL . $transactionResponseMessage . PHP_EOL . '")', 'cBestellNr = "' . $orderNo . '"'); // Updates the value into the database
        } else {
            NovalnetGateway::performDbExecution('tbestellung', 'cKommentar = CONCAT(cKommentar, "' . PHP_EOL . $transactionResponseMessage . PHP_EOL . (!empty($comments) ?  $comments : '') . '")', 'cBestellNr = "' . $orderNo . '"'); // Updates the value into the database
        }
    }
    if($sendOrderMail) {
        $jtlPaymentmethod = PaymentMethod::create($orderObj->payment_id);
        $jtlPaymentmethod->sendMail($orderRef->kBestellung, MAILTEMPLATE_BESTELLUNG_AKTUALISIERT);
    }
    
    echo $transactionResponseMessage;
    exit;
}
