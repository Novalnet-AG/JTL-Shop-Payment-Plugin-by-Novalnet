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
 * Script : Novalnet.callback.class.php
 *
 */

require_once('includes/globalinclude.php');
require_once(PFAD_INCLUDES_MODULES . 'PaymentMethod.class.php' );
require_once(PFAD_ROOT. PFAD_CLASSES . 'class.JTL-Shop.Plugin.php' );

/**
 * Handles callback request
 *
 * @param none
 * @return none
 */
function performCallbackExecution()
{
    global $processTestMode, $processDebugMode, $oPlugin, $jtlPaymentClass;

    //get plugin object
    $oPlugin = Plugin::getPluginById('novalnetag');

    require_once(PFAD_PLUGIN . $oPlugin->cPluginID . '/' . PFAD_PLUGIN_VERSION . $oPlugin->nVersion . '/' . PFAD_PLUGIN_PAYMENTMETHOD . PFAD_CLASSES . 'class.Novalnet.php');
    require_once(PFAD_INCLUDES . 'mailTools.php');

    $jtlPaymentClass = new NovalnetGateway();
    $aryCaptureParams = $_REQUEST;
    $processTestMode  = $oPlugin->oPluginEinstellungAssoc_arr['callback_testmode']; # Update into false when switch into LIVE
    $processDebugMode = $oPlugin->oPluginEinstellungAssoc_arr['callback_debugmode']; # Update into true to debug mode

    $lineBreak = '<br/>';
    $jtlVendorScript  = new NovalnetVendorScript($aryCaptureParams);

    //Affiliate process
    if (!empty($aryCaptureParams['vendor_activation']))
    {
        $jtlVendorScript->updateAffiliateDatas($aryCaptureParams);

        $callbackScriptText = 'Novalnet callback script executed successfully with Novalnet account activation information.';
        //Send notification mail to Merchant
        $jtlVendorScript->sendMailNotification(array(
            'comments' => $callbackScriptText
        ), true);
        $jtlVendorScript->displayMessage($callbackScriptText);
    }
    else
    {
        $jtlVendorParams  = $jtlVendorScript->getCaptureParams();
        $jtlVendorHistory = $jtlVendorScript->getOrderByIncrementId($jtlVendorParams);
        $orderId = $jtlVendorHistory->cBestellNr;
        $orderTotalAmount = $jtlVendorHistory->nn_amount;
        $currencyFormat = NovalnetGateway::getPaymentCurrency($orderId);

            //Cancellation of a Subscription
            if (($jtlVendorParams['payment_type'] == 'SUBSCRIPTION_STOP' && $jtlVendorParams['status'] == 100 && $jtlVendorParams['tid_status'] == 100) || ($jtlVendorParams['status'] != 100 && ($jtlVendorParams['subs_billing'] == 1) && $jtlVendorScript->getPaymentTypeLevel() == 0 ))
            {
                $cancelReason = !empty($jtlVendorParams['termination_reason']) ? $jtlVendorParams['termination_reason'] : $jtlPaymentClass->getResponseText($jtlVendorParams);

                $jtlVendorScript->updateSubscriptionReason(array(
                    'termination_reason' => $cancelReason,
                    'termination_at' => date('Y-m-d H:i:s'),
                    'tid' => $jtlVendorParams['shop_tid'],
                ));

                $callbackScriptText = PHP_EOL . 'Novalnet callback script received. Subscription has been stopped for the TID: '.$jtlVendorParams['shop_tid']. ' on ' . date('d.m.Y H:i:s');
                $callbackScriptText .= PHP_EOL . 'Subscription has been canceled due to: '. $cancelReason . PHP_EOL;
			
                $jtlPaymentClass->setOrderStatus($orderId, $oPlugin->oPluginEinstellungAssoc_arr['subscription_order_status']);

                $jtlVendorScript->callbackFinalProcess($callbackScriptText, $orderId, $jtlVendorHistory, true);
            }

            $query = Shop::DB()->query("SELECT SUM(nCallbackAmount) AS totalAmount FROM xplugin_novalnetag_tcallback WHERE cBestellnummer = '".$orderId."'", 1);
            $orderPaidAmount = $query->totalAmount + $jtlVendorParams['amount'];
            $callbackScriptText ='';

                if ($jtlVendorScript->getPaymentTypeLevel() == 2 && $jtlVendorParams['status'] == 100 && $jtlVendorParams['tid_status'] == 100) // level 2 payments - Types of Collections available
                {
                    if (in_array($jtlVendorParams['payment_type'],array('INVOICE_CREDIT','ONLINE_TRANSFER_CREDIT')))
                    {
                        if ($query->totalAmount < $orderTotalAmount)
                        {
                            $callbackScriptText = PHP_EOL . 'Novalnet Callback Script executed successfully for the TID: '.$jtlVendorParams['shop_tid'] .' with amount: ' . number_format( gibPreisString($jtlVendorParams['amount']) / 100, 2, ',', '' ) .' '. $currencyFormat .' on ' . date('d.m.Y H:i:s') . '. Please refer PAID transaction in our Novalnet Merchant Administration with the TID:' . $jtlVendorParams['tid'] . PHP_EOL;

                            if ($orderPaidAmount >= $orderTotalAmount)
                            {
                                if ($orderPaidAmount > $orderTotalAmount)
                                {
                                    $callbackGreaterAmount = 'Customer has paid more than the Order amount.' . PHP_EOL;
                                }
                                $jtlPaymentmethod = new PaymentMethod($jtlVendorHistory->payment_id);
                                $jtlPaymentmethod->name = $jtlVendorHistory->cZahlungsartName;
                                $incomingPayment = new stdClass();
                                $incomingPayment->fBetrag = $jtlVendorHistory->nn_amount / 100;
                                $incomingPayment->cISO = $currencyFormat;
                                $incomingPayment->cHinweis = $jtlVendorParams['shop_tid'];
                                $jtlPaymentmethod->addIncomingPayment( $jtlVendorHistory, $incomingPayment ); // Adds the current transaction into the shop's order table

                                $callbackStatus = ($jtlVendorParams['payment_type'] == 'INVOICE_CREDIT') ? $oPlugin->oPluginEinstellungAssoc_arr[$jtlVendorHistory->payment_type.'_callback_status'] : $oPlugin->oPluginEinstellungAssoc_arr[$jtlVendorHistory->payment_type.'_set_order_status'];
								
                                $jtlPaymentClass->setOrderStatus($orderId, $callbackStatus, true);
                                
                                Shop::DB()->query('UPDATE xplugin_novalnetag_tnovalnet_status SET nStatuswert="' . $jtlVendorParams['tid_status'] . '" WHERE cNnorderid = "' . $orderId . '"', 4);
                            }
                            $jtlVendorScript->callbackFinalProcess($callbackScriptText, $orderId, $jtlVendorHistory, false, true, $callbackGreaterAmount);
                        }
                        $jtlVendorScript->displayMessage('Novalnet callback received. Callback Script executed already. Refer Order :'.$orderId);
                    }

                    else
                    {
                        $error = 'Novalnet Callbackscript received. Payment type ( '.$jtlVendorParams['payment_type'].' ) is not applicable for this process!';
                        $jtlVendorScript->displayMessage($error);
                    }
                }

                elseif ($jtlVendorScript->getPaymentTypeLevel() == 1 && $jtlVendorParams['status'] == 100 && $jtlVendorParams['tid_status'] == 100) //level 1 payments - Types of Chargebacks
                {
                    if(in_array($jtlVendorParams['payment_type'],array('CREDITCARD_BOOKBACK','PAYPAL_BOOKBACK','REFUND_BY_BANK_TRANSFER_EU'))) {
                        $processText = 'Novalnet callback received. Refund/Bookback executed successfully for the TID:';
                    } else {
                        $processText = 'Novalnet callback received. Chargeback executed successfully for the TID:';
                    }
                    $callbackScriptText = PHP_EOL . $processText . $jtlVendorParams['tid_payment'] .' '. 'amount:'. number_format( gibPreisString($jtlVendorParams['amount']) / 100, 2, ',', '' ) .' ' . $currencyFormat .' on '.date('d.m.Y H:i:s') . ' . The subsequent TID: ' .$jtlVendorParams['tid'] . PHP_EOL;

                    $jtlVendorScript->callbackFinalProcess($callbackScriptText, $orderId, $jtlVendorHistory);
                }

                elseif ($jtlVendorScript->getPaymentTypeLevel() == 0 && $jtlVendorParams['status'] == 100 && in_array($jtlVendorParams['tid_status'],array('90', '91', '98', '99', '100'))) //level 0 payments - Types of payments
                {
                    if ($jtlVendorParams['subs_billing'] == 1)
                    {
                        $callbackScriptText = PHP_EOL . 'Novalnet Callback Script executed successfully for the subscription TID: '. $jtlVendorParams['shop_tid'].' with amount '. number_format( gibPreisString($jtlVendorParams['amount']) / 100, 2, ',', '' ) . ' '. $currencyFormat .' on '. date('d.m.Y H:i:s') . '. Please refer PAID transaction in our Novalnet Merchant Administration with the TID:'.$jtlVendorParams['tid']. PHP_EOL;

                         $callbackScriptText .= '<br>Next Charging Date : '. date('d.m.Y H:i:s', strtotime(!empty($jtlVendorParams['next_subs_cycle']) ? $jtlVendorParams['next_subs_cycle'] : $jtlVendorParams['paid_until']));
						
                        $jtlVendorScript->callbackFinalProcess($callbackScriptText, $orderId, $jtlVendorHistory, true);
                    }
                    elseif ($jtlVendorParams['payment_type'] == 'PAYPAL')
                    {
                        if ($query->totalAmount < $orderTotalAmount)
                        {
                            $callbackScriptText = PHP_EOL . 'Novalnet Callback Script executed successfully for the TID: ' .$jtlVendorParams['shop_tid'] . ' with amount: ' . number_format( gibPreisString($jtlVendorParams['amount']) / 100, 2, ',', '' ) .' '.$currencyFormat . ' on ' . date('d.m.Y H:i:s');

                            $jtlPaymentClass->setOrderStatus($orderId, $oPlugin->oPluginEinstellungAssoc_arr[$jtlVendorHistory->payment_type.'_set_order_status'], true);
                            $jtlPaymentmethod = new PaymentMethod($jtlVendorHistory->payment_id);
                            $jtlPaymentmethod->name = $jtlVendorHistory->cZahlungsartName;
                            $incomingPayment = new stdClass();
                            $incomingPayment->fBetrag = $jtlVendorHistory->nn_amount / 100;
                            $incomingPayment->cISO = $currencyFormat;
                            $incomingPayment->cHinweis = $jtlVendorParams['shop_tid'];
                            $jtlPaymentmethod->addIncomingPayment( $jtlVendorHistory, $incomingPayment ); // Adds the current transaction into the shop's order table

                            Shop::DB()->query('UPDATE xplugin_novalnetag_tnovalnet_status SET nStatuswert="' . $jtlVendorParams['tid_status'] . '" WHERE cNnorderid = "' . $orderId . '"', 4);

                            $jtlVendorScript->callbackFinalProcess($callbackScriptText, $orderId, $jtlVendorHistory, false, true);
                        }
                            $jtlVendorScript->displayMessage('Novalnet Callbackscript received. Order already Paid');
                     }
                     else
                     {
                        $error = 'Novalnet Callbackscript received. Payment type ( '.$jtlVendorParams['payment_type'].' ) is not applicable for this process!';
                        $jtlVendorScript->displayMessage($error);
                     }
                }
        /*
        * Error section : When status executing other than 100
        */
        $jtlVendorScript->displayMessage( ( $jtlVendorParams['tid_status'] != 100 || $jtlVendorParams['status'] != 100 ) ? 'Novalnet callback received. Status is not valid.' : 'Novalnet callback received. Callback Script executed already.' );
    }
}

class NovalnetVendorScript{

    /** @Array Type of payment available - Level : 0 */
    protected $aryPayments = array('CREDITCARD','INVOICE_START','DIRECT_DEBIT_SEPA','GUARANTEED_INVOICE_START','PAYPAL','ONLINE_TRANSFER','IDEAL','EPS','PAYSAFECARD','GIROPAY','GUARANTEED_DIRECT_DEBIT_SEPA');

    /** @Array Type of Chargebacks available - Level : 1 */
    protected $aryChargebacks = array('RETURN_DEBIT_SEPA','REVERSAL','CREDITCARD_BOOKBACK','CREDITCARD_CHARGEBACK','REFUND_BY_BANK_TRANSFER_EU','PAYPAL_BOOKBACK');

    /** @Array Type of CreditEntry payment and Collections available - Level : 2 */
    protected $aryCollection = array('INVOICE_CREDIT','GUARANTEED_INVOICE_CREDIT','CREDIT_ENTRY_CREDITCARD','CREDIT_ENTRY_SEPA','DEBT_COLLECTION_SEPA','DEBT_COLLECTION_CREDITCARD','ONLINE_TRANSFER_CREDIT');

    /** @Array Callback Capture parameters */
    protected $aryCaptureParams = array();

    /** @IP-ADDRESS Novalnet IP, is a fixed value, DO NOT CHANGE!!!!! */
    protected $ipAllowed = array('195.143.189.210','195.143.189.214');

    protected $aPaymentTypes = array(
        'novalnet_invoice'      => array('INVOICE_CREDIT','INVOICE_START', 'SUBSCRIPTION_STOP'),
        'novalnet_prepayment'   => array('INVOICE_CREDIT','INVOICE_START', 'SUBSCRIPTION_STOP'),
        'novalnet_paypal'       => array('PAYPAL', 'SUBSCRIPTION_STOP','PAYPAL_BOOKBACK'),
        'novalnet_banktransfer' => array('ONLINE_TRANSFER','ONLINE_TRANSFER_CREDIT'),
        'novalnet_cc'           => array('CREDITCARD', 'CREDITCARD_BOOKBACK', 'CREDITCARD_CHARGEBACK', 'SUBSCRIPTION_STOP'),
        'novalnet_ideal'        => array('IDEAL'),
        'novalnet_sepa'         => array('DIRECT_DEBIT_SEPA', 'GUARANTEED_DIRECT_DEBIT_SEPA', 'RETURN_DEBIT_SEPA', 'SUBSCRIPTION_STOP'),
        'novalnet_eps'          => array('EPS'),
        'novalnet_giropay'      => array('GIROPAY')
    );

    protected $paramsRequired = array();

    protected $affParamsRequired = array();


    /**
     *
     * Constructor
     *
     */
    function __construct($aryCapture)
    {
        $this->paramsRequired = array('vendor_id', 'tid', 'payment_type', 'status', 'tid_status');

        $this->affParamsRequired = array('vendor_id','vendor_authcode','aff_id','aff_authcode','aff_accesskey','product_id');

        if(!empty($aryCapture['subs_billing'])){
            array_push($this->paramsRequired, 'signup_tid');
        }elseif (isset($aryCapture['payment_type']) && in_array($aryCapture['payment_type'], array_merge($this->aryChargebacks, $this->aryCollection))) {
            array_push($this->paramsRequired, 'tid_payment');
        }
        $this->arycaptureparams = self::validateCaptureParams($aryCapture);
    }

    /**
     * Returns callback request parameters
     *
     * @param none
     * @return array
     */
    function getCaptureParams()
    {
        return $this->arycaptureparams;
    }

    /**
     * Throws callback script texts and errors
     *
     * @param string $errorMsg
     * @param bool   $forceDisplay
     * @return none
     */
    function displayMessage($errorMsg, $forceDisplay = false)
    {
        global $processDebugMode;
        if ($processDebugMode || $forceDisplay){
            echo $errorMsg;
        }
        exit;
    }

    /**
     * Get the payment type level for the request payment
     *
     * @param none
     * @return integer
     */
    function getPaymentTypeLevel()
    {
        if (in_array($this->arycaptureparams['payment_type'], $this->aryPayments)) {
            return 0;
        } else if (in_array($this->arycaptureparams['payment_type'], $this->aryChargebacks)) {
            return 1;
        } else if (in_array($this->arycaptureparams['payment_type'], $this->aryCollection)) {
            return 2;
        }
    }

    /**
     * Triggers mail notification to the mail address specified
     *
     * @param array $mailDatas
     * @param bool $affiliate
     * @return bool
     */
    function sendMailNotification($mailDatas, $affiliate = false)
    {
        global $oPlugin;

        $emailFrom = Shop::DB()->query("SELECT cWert FROM teinstellungen WHERE cName='email_master_absender'",1);
        $emailToAddr = $oPlugin->oPluginEinstellungAssoc_arr['callback_to_address'];
        $emailBccAddr = $oPlugin->oPluginEinstellungAssoc_arr['callback_bcc_address'];

        if ($oPlugin->oPluginEinstellungAssoc_arr['callback_notification_send'] == 1 && !empty($mailDatas)) {

            //Reporting Email Addresses Settings
            $emailFromAddr = $emailFrom->cWert;
            //sender email addr., manditory, adapt it
            $validEmail = true;

            $emailAddr = explode( ',', $emailToAddr.','.$emailBccAddr );
            $emailAddr = array_map( 'trim', $emailAddr );

            foreach ( $emailAddr as $addr ) {
                if ( !empty ( $addr ) && !valid_email( $addr ) ) {
                       $validEmail = false;
                       break;
                }
            }

            if ( $validEmail && !empty($emailToAddr) ) {
                $emailSubject  = 'Novalnet Callback script notification'; //adapt if necessary;
                $emailBody     = (!$affiliate) ? 'Order :'.$mailDatas['orderNo'].' <br/> Message : '.$mailDatas['comments'] : ' <br/> Message : '. $mailDatas['comments'];//Email text's 1. line, can be let blank, adapt for your need
                $emailBody     = str_replace('<br />', PHP_EOL , $emailBody);
                $emailFromName = 'Novalnet'; // Sender name, adapt
                $headers  = 'Content-Type: text/html; charset=iso-8859-1'. "\r\n";
                $headers .= "From: " . $emailFromName . " <" . $emailFromAddr . ">\r\n";

                if ( !empty($emailBccAddr) )
                    $headers .= 'BCC: ' . $emailBccAddr . "\r\n";

                $sendmail = mail($emailToAddr , $emailSubject, $emailBody, $headers);

                if ( $sendmail )
                    echo 'Mail sent!';
                else
                    echo 'Mail not sent!';
            }
            else
                echo 'Mail not sent!';
            return true;
        }
        return false;
    }

    /**
     * Validates the request parameters
     *
     * @param array $aryCaptureParams
     * @return array
     */
    function validateCaptureParams($aryCaptureParams)
    {
        global $processTestMode;

        if (!in_array(getRealIp(), $this->ipAllowed) && !$processTestMode) {

            self::displayMessage('Unauthorised access from the IP '. getRealIp(), true);
        }

        if (!empty($aryCaptureParams['vendor_activation']))
        {
            self::validateRequiredParameters($this->affParamsRequired, $aryCaptureParams);
        } else {
            self::validateRequiredParameters($this->paramsRequired, $aryCaptureParams);

            if (!in_array($aryCaptureParams['payment_type'], array_merge( $this->aryCollection, $this->aryChargebacks , $this->aryPayments , array('SUBSCRIPTION_STOP')))){
                self::displayMessage('Novalnet callback received. Payment type['.$aryCaptureParams["payment_type"].'] is mismatched!');
            }

            if ($aryCaptureParams['payment_type'] != 'SUBSCRIPTION_STOP' && ( !is_numeric( $aryCaptureParams['amount'] ) || $aryCaptureParams['amount'] < 0 ) ) {
                self::displayMessage( 'Novalnet callback received. The requested amount ('. $aryCaptureParams['amount'] .') is not valid' );
            }

            if ($aryCaptureParams['subs_billing'] != 1 && in_array($aryCaptureParams['payment_type'], array_merge($this->aryChargebacks, array('INVOICE_CREDIT')))) {
                if (!is_numeric($aryCaptureParams['tid_payment']) || strlen($aryCaptureParams['tid_payment']) != 17) {
                    self::displayMessage('Novalnet callback received. Invalid TID [' . $aryCaptureParams[   "tid_payment"] . '] for Order.');
                }
            }

            if ($aryCaptureParams['subs_billing'] == 1 && strlen($aryCaptureParams['signup_tid']) != 17) {
                self::displayMessage('Novalnet callback received. Invalid TID [' . $aryCaptureParams["signup_tid"] . '] for Order.');
            }

            if (strlen($aryCaptureParams['tid'])!=17 || !is_numeric($aryCaptureParams['tid'])){
                self::displayMessage('Novalnet callback received. TID [' . $aryCaptureParams['tid'] . '] is not valid.');
            }
            $aryCaptureParams['shop_tid'] = (!empty($aryCaptureParams['signup_tid']) && ($aryCaptureParams['payment_type'] == 'SUBSCRIPTION_STOP' || $aryCaptureParams['subs_billing'] == 1)) ? $aryCaptureParams['signup_tid'] : (in_array($aryCaptureParams['payment_type'], array_merge($this->aryChargebacks, $this->aryCollection)) ? $aryCaptureParams['tid_payment'] : $aryCaptureParams['tid']);
        }

        return $aryCaptureParams;

    }

    /**
     * Get order details from the shop's database
     *
     * @param array $aryCaptureValues
     * @return array
     */
    function getOrderByIncrementId($aryCaptureValues)
    {
        $order = Shop::DB()->query('SELECT cNnorderid FROM xplugin_novalnetag_tnovalnet_status WHERE nNntid = "' . $aryCaptureValues['shop_tid'] . '"', 1);

        $uniqueOrderValue = !empty($order->cNnorderid) ? self::getUniqueOrderValue($order->cNnorderid)  : '';

        $orderNo = (!empty($aryCaptureValues['order_no'])) ? $aryCaptureValues['order_no'] : (!empty( $aryCaptureValues['order_id']) ? $aryCaptureValues['order_id'] : '');
		
        if (empty($uniqueOrderValue))
        {
            if ($orderNo)
            {
                $order = Shop::DB()->query('SELECT kBestellung FROM tbestellung WHERE cBestellNr = "' . $orderNo . '"', 1);

                if (empty($order->kBestellung)) {
                    self::displayMessage('Transaction mapping failed');
                }

                $uniqueOrderValue = self::getUniqueOrderValue($orderNo); // Gets unique order ID for the particular order stored in shop database

                $order = new Bestellung($uniqueOrderValue);
                $this->addOrderObjectValues($order, true); // Adds up additional order object values for callback process
                $this->handleCommunicationBreak($order, $aryCaptureValues); // Handles communication failure scenario
            } else {
                self::displayMessage('Transaction mapping failed');
            }
        }
		
        $order = new Bestellung($uniqueOrderValue); // Loads order object from shop
        $this->addOrderObjectValues($order); // Adds up additional order object values for callback process

        if ($orderNo && $order->cBestellNr != $orderNo) {
            self::displayMessage('Order number not valid');
        }

        if ((!array_key_exists($order->payment_type, $this->aPaymentTypes)) || !in_array($aryCaptureValues['payment_type'], $this->aPaymentTypes[$order->payment_type])) {
            self::displayMessage('Novalnet callback received. Payment type [' . $aryCaptureValues['payment_type'] . '] is mismatched!');
        }

        return $order;
    }

    /**
     * To get order object's kBestellung value
     *
     * @param string $orderNo
     * @return integer
     */
    public function getUniqueOrderValue($orderNo)
    {
        $uniqueValue = Shop::DB()->query('SELECT kBestellung FROM tbestellung WHERE cBestellNr = "' . $orderNo . '"', 1);

        return $uniqueValue->kBestellung;
    }

    /**
     * To check the required parameters is present or not
     *
     * @param array $paramsRequired
     * @param array $aryCaptureParams
     * @return bool
     */
    function validateRequiredParameters($paramsRequired, $aryCaptureParams)
    {
        global $lineBreak;
        foreach ($paramsRequired as $k => $v) {
            if (empty($aryCaptureParams[$v])) {
                self::displayMessage('Required param ('.$v.') missing!'.$lineBreak);
            }
        }
        return true;
    }

    /**
     * Handling communication breakup
     *
     * @param array $order
     * @param array $aryCaptureParams
     * @return none
     */
    function handleCommunicationBreak($order, $aryCaptureParams)
    {
        global $oPlugin, $jtlPaymentClass;

        $jtlPaymentmethod = new PaymentMethod($order->payment_id);
        $orderId = $order->cBestellNr;

        $txn_message = '';

        if (!empty($aryCaptureParams['test_mode']))
            $txn_message = (($order->kSprache == 1) ? 'Testbestellung' : 'Test order') . PHP_EOL;
            $txn_message.= (($order->kSprache == 1) ? 'Novalnet-Transaktions-ID:' : 'Novalnet transaction ID:') . $aryCaptureParams['shop_tid']  . PHP_EOL;

        $affDetails = self::affiliateUserCheck($order->kKunde);

        if(($aryCaptureParams['status'] == 100 && in_array($aryCaptureParams['tid_status'], array(90, 91, 98, 99, 100)))) {
			
            $jtlPaymentmethod->name = $order->cZahlungsartName;
            $incomingPayment = new stdClass();
            $incomingPayment->fBetrag = !empty($order->nn_amount) ? ($order->nn_amount)/100 : $order->fGesamtsumme;
            $incomingPayment->cISO = NovalnetGateway::getPaymentCurrency($orderId);
            $incomingPayment->cHinweis = $aryCaptureParams['shop_tid'];
            $jtlPaymentmethod->addIncomingPayment($order, $incomingPayment ); // Adds the current transaction into the shop's order table
            
            if ($order->payment_type == 'novalnet_paypal' && $aryCaptureParams['tid_status'] == 90) {
				$jtlPaymentClass->setOrderStatus($orderId, $oPlugin->oPluginEinstellungAssoc_arr['paypal_pending_status'], true);
			} else {
				$jtlPaymentClass->setOrderStatus($orderId, $oPlugin->oPluginEinstellungAssoc_arr[$order->payment_type.'_set_order_status'], true);
			}
            
        } else {
            $txn_message .=  !empty( $aryCaptureParams['status_text']) ? $aryCaptureParams['status_text'] : (!empty($aryCaptureParams['status_desc']) ? $aryCaptureParams['status_desc'] : (!empty ( $aryCaptureParams['status_message']) ? $aryCaptureParams['status_message'] : ''));

            $jtlPaymentClass->setOrderStatus($orderId, $oPlugin->oPluginEinstellungAssoc_arr['cancel_order_status'],false, true);
        }
        NovalnetGateway::addReferenceToComment($order->kBestellung, $txn_message);

        $orderObj = new Bestellung($order->kBestellung);

        $customerEmail = !empty($aryCaptureParams['email']) ? $aryCaptureParams['email'] : '';

        NovalnetGateway::insertOrderIntoDBForFailure($order, $aryCaptureParams['shop_tid'], $order->payment_type, $orderObj->cKommentar, $aryCaptureParams['tid_status'], $customerEmail, $affDetails);

        if ( trim( $oPlugin->oPluginEinstellungAssoc_arr['vendorid'] ) != $aryCaptureParams['vendor_id'] ) {
            $insertAffiliate = new stdClass();
            $insertAffiliate->nAffId      = $aryCaptureParams['vendor_id'];
            $insertAffiliate->cCustomerId = $order->kKunde;
            $insertAffiliate->nAffOrderNo = $order->cBestellNr;
            Shop::DB()->insertRow( 'xplugin_novalnetag_taff_user_detail', $insertAffiliate );
        }
		
		if (!empty($aryCaptureParams['subs_id'])) {
            $insertSubscription = new stdClass();
            $insertSubscription->cBestellnummer = $orderId;
            $insertSubscription->nSubsId        = $aryCaptureParams['subs_id'];
            $insertSubscription->nTid           = $aryCaptureParams['tid'];
            $insertSubscription->dSignupDate    = date('Y-m-d H:i:s');

            Shop::DB()->insertRow('xplugin_novalnetag_tsubscription_details', $insertSubscription);
        }
		
        $jtlPaymentmethod->sendMail($order->kBestellung, MAILTEMPLATE_BESTELLUNG_AKTUALISIERT);
        $callbackScriptText = html_entity_decode($order->cZahlungsartName) . ' payment status updated';
        $this->callbackFinalProcess($callbackScriptText, $orderId, $order, ((!in_array($order->payment_type, array('novalnet_invoice','novalnet_prepayment')) && ($aryCaptureParams['tid_status'] == 100)) ? false : true), false);
    }

    /**
     * To retrieve affiliate user for communication failure
     *
     * @param integer $customerNo
     * @return array
     */
    function affiliateUserCheck($customerNo)
    {
        $affCustomer = Shop::DB()->query("SELECT nAffId FROM xplugin_novalnetag_taff_user_detail WHERE cCustomerId=" . $customerNo . " ORDER BY kId DESC LIMIT 1",1);

        if (is_object($affCustomer)) {
            $affAuthcode = Shop::DB()->query("SELECT cAffAuthcode FROM xplugin_novalnetag_taffiliate_account_detail WHERE nAffId=" . $affCustomer->nAffId . "'",1);
        }

        $vendorId = !empty($affCustomer->nAffId) ? $affCustomer->nAffId : '';
        $authcode = !empty($affAuthcode->cAffAuthcode) ? $affAuthcode->cAffAuthcode : '';

        return array('vendor' => $vendorId,
                     'authcode' => $authcode);
    }

    /**
     * Update affiliate process details into the Novalnet table for reference
     *
     * @param array $datas
     * @return bool
     */
    function updateAffiliateDatas($datas)
    {
        $insertAffiliate->nVendorId       = $datas['vendor_id'];
        $insertAffiliate->cVendorAuthcode = $datas['vendor_authcode'];
        $insertAffiliate->nProductId      = $datas['product_id'];
        $insertAffiliate->cProductUrl     = $datas['product_url'];
        $insertAffiliate->dActivationDate = !empty($datas['activation_date']) ? $datas['activation_date'] : date('d.m.Y H:i:s');
        $insertAffiliate->nAffId          = $datas['aff_id'];
        $insertAffiliate->cAffAuthcode    = $datas['aff_authcode'];
        $insertAffiliate->cAffAccesskey   = $datas['aff_accesskey'];
        Shop::DB()->insertRow('xplugin_novalnetag_taffiliate_account_detail', $insertAffiliate);

        return true;
    }

    /**
     * Update cancellation reason for subscription into the Novalnet table
     *
     * @param array $datas
     * @return bool
     */
    function updateSubscriptionReason($datas)
    {
        if (!empty($datas['termination_reason'])) {
            Shop::DB()->query('UPDATE xplugin_novalnetag_tsubscription_details SET cTerminationReason = "'.$datas['termination_reason'].'", dTerminationAt = "'.$datas['termination_at'].'" WHERE nTid ="'.$datas['tid'] . '"', 1);
        }

        return true;
    }

    /**
     * Update callback comments into the shop's order and Novalnet tables
     *
     * @param array $datas
     * @return bool
     */
    function updateCallbackComments($datas)
    {
        Shop::DB()->query("UPDATE tbestellung SET cKommentar = CONCAT(cKommentar, '" . $datas['comments'] . "') WHERE cBestellNr = '" . $datas['orderNo'] ."'" ,1);

        Shop::DB()->query("UPDATE xplugin_novalnetag_tnovalnet_status SET cKommentare = CONCAT(cKommentare, '" . $datas['comments'] . "<br/>" ."') , dDatum = CONCAT(dDatum, '" . date('d.m.Y H:i:s') . "<br/>" ."' ) WHERE cNnorderid = '" . $datas['orderNo'] ."'" , 1);

        return true;
    }

    /**
     * Performs final callback process
     *
     * @param array $callbackScriptText
     * @param string $orderId
     * @param array $orderReference
     * @param bool $initialAmount
     * @param bool $updateComments
     * @param string $greaterAmountText
     * @return none
     */
    function callbackFinalProcess($callbackScriptText, $orderId, $orderReference, $initialAmount = false, $updateComments = true, $greaterAmountText = '')
    {
        //update callback comments in Novalnet table
        if ($updateComments) {
            $this->updateCallbackComments(array(
                'comments' => $callbackScriptText,
                'orderNo'  => $orderId
            ));
        }

        if (!empty($greaterAmountText))
            $callbackScriptText = $callbackScriptText . $greaterAmountText;

        //Send notification mail to Merchant
        $this->sendMailNotification(array(
            'comments' => $callbackScriptText,
            'orderNo'  => $orderId
        ));

        $paymentInfo = array('payment' => $orderReference->cZahlungsartName,'payment_type' => $orderReference->payment_type);

        // Log callback process (for all types of payments default)
        $this->logCallbackProcess($this->arycaptureparams, $paymentInfo, $orderId, $initialAmount);
        $this->displayMessage($callbackScriptText);
    }

    /**
     * To log callback process into the callback table
     *
     * @param array $datas
     * @param array $paymentInfo
     * @param string $orderNo
     * @param bool $initialAmount
     * @return bool
     */
    function logCallbackProcess($datas, $paymentInfo, $orderNo, $initialAmount)
    {
        $orderAmount = Shop::DB()->query('SELECT fGesamtsumme FROM tbestellung WHERE cBestellNr = '.$orderNo,1);

        $insertCallback->dDatum          = date('Y-m-d H:i:s');
        $insertCallback->cZahlungsart    = $paymentInfo['payment'];
        $insertCallback->nReferenzTid    = $datas['tid'];
        $insertCallback->nCallbackTid    = $datas['shop_tid'];
        $insertCallback->nCallbackAmount = ($initialAmount) ? 0 : (($paymentInfo['payment_type'] == 'novalnet_paypal') ? ($orderAmount->fGesamtsumme * 100) : $datas['amount']);
        $insertCallback->cWaehrung       = NovalnetGateway::getPaymentCurrency($orderNo);
        $insertCallback->cBestellnummer  = $orderNo;
        Shop::DB()->insertRow('xplugin_novalnetag_tcallback', $insertCallback);

        return true;
    }

    /**
     * Adds up additional order object values for callback process
     *
     * @param object $order
     * @param boolean $paymentFailure
     * @return none
     */
    public function addOrderObjectValues(&$order, $paymentFailure = false)
    {
        global $oPlugin;
		
		if ( $paymentFailure ) {			
			$paymentMethod = Shop::DB()->query('SELECT cName FROM tzahlungsart WHERE kZahlungsart='. $order->kZahlungsart,1);
			$paymentMethod  = str_replace(' ', '', strtolower($paymentMethod->cName));
			$order->payment_id = 'kplugin_' . $oPlugin->kPlugin . '_' . $paymentMethod;
			$order->payment_type = NovalnetGateway::getPaymentMethod($paymentMethod, $oPlugin->kPlugin, true);
			$orderAmount = Shop::DB()->query('SELECT fGesamtsumme FROM tbestellung WHERE cBestellNr = '. $order->cBestellNr, 1);
			$order->nn_amount = $orderAmount->fGesamtsumme;
		} else {
			$orderValues = Shop::DB()->query('SELECT nBetrag, cZahlungsmethode FROM xplugin_novalnetag_tnovalnet_status WHERE cNnorderid='.$order->cBestellNr,1);
			$order->payment_type = $orderValues->cZahlungsmethode;
			$order->payment_id = NovalnetGateway::getPaymentMethod($order->payment_type, $oPlugin->kPlugin);
			$order->nn_amount = $orderValues->nBetrag;
		}
    }
}
