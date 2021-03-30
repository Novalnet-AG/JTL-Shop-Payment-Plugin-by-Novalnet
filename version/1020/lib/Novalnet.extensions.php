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
 * Script : Novalnet.extensions.php
 *
 */
header('Access-Control-Allow-Origin: *');

// Request
$request = !empty($_REQUEST['apiParams']) ? $_REQUEST : array('apiParams' => $_REQUEST);

require_once( $request['apiParams']['pluginInc'] );

//get plugin object
$oPlugin = Plugin::getPluginById('novalnetag');

include_once( PFAD_ROOT . PFAD_PLUGIN . $oPlugin->cPluginID . '/' . PFAD_PLUGIN_VERSION . $oPlugin->nVersion . '/' . PFAD_PLUGIN_PAYMENTMETHOD . PFAD_CLASSES . 'class.Novalnet.php');
include_once( PFAD_ROOT . PFAD_PLUGIN . $oPlugin->cPluginID . '/' . PFAD_PLUGIN_VERSION . $oPlugin->nVersion . '/' . PFAD_PLUGIN_PAYMENTMETHOD . PFAD_CLASSES . 'class.NovalnetValidation.php');
include_once( PFAD_ROOT . PFAD_CLASSES . 'class.JTL-Shop.Bestellung.php');

$orderNo = $request['apiParams']['orderNo'];

$novalnetObj = new NovalnetGateway();

$currency = NovalnetGateway::getPaymentCurrency($orderNo);

//get language texts
$placeholder = array('__NN_transaction_capture_text_1','__NN_transaction_capture_text_2','__NN_transaction_capture_text_3','__NN_transaction_void_text_1','__NN_transaction_void_text_2','__NN_transaction_void_text_3','__NN_refund_message_1','__NN_refund_message_2','__NN_refund_message_3','__NN_new_TID_message','__NN_amount_update_message_1','__NN_amount_update_message_2','__NN_amount_update_message_3','__NN_amount_update_message_4','__NN_subscription_cancelled','__NN_test_order','__NN_tid_label');

$apicallTexts = NovalnetGateway::getLanguageText($placeholder);

//get order details
$orderDetails  = Shop::DB()->query("SELECT cZahlungsmethode, nNntid, nErstattungsbetrages, nBetrag FROM xplugin_novalnetag_tnovalnet_status WHERE cNnorderid = '".$orderNo."'", 1);

$paymentMethod = $orderDetails->cZahlungsmethode;

//get invoice details
if (in_array($paymentMethod, array('novalnet_invoice', 'novalnet_prepayment'))) {
    $transDetails = Shop::DB()->query("SELECT cbankName, cbankCity, cbankIban, cbankBic, bTestmodus, cReferenceValues FROM xplugin_novalnetag_tpreinvoice_transaction_details WHERE cBestellnummer = '".$orderNo."'", 1);
}

//get order reference number
$orderRef = Shop::DB()->query("SELECT kBestellung FROM tbestellung WHERE cBestellNr = '".$orderNo."'", 1);

//new order reference
$orderObj = new Bestellung($orderRef->kBestellung);
$orderObj->payment_id = NovalnetGateway::getPaymentMethod($orderDetails->cZahlungsmethode, $oPlugin->kPlugin);

list( $vendorId, $authcode, $productId, $tariffId, $proxy ) = NovalnetGateway::getConfigurationDetails($orderNo);

$nnBreak   = "<br/>";
$nnApiParams = array();

    if (!empty($vendorId) && !empty($authcode) && !empty($productId) && !empty($tariffId) && !empty($orderDetails->nNntid)) {
        $nnApiParams['vendor']    = $vendorId;
        $nnApiParams['auth_code'] = $authcode;
        $nnApiParams['product']   = $productId;
        $nnApiParams['tariff']    = $tariffId;
        $nnApiParams['key']       = $novalnetObj->setPaymentKey($paymentMethod);
        $nnApiParams['tid']       = $orderDetails->nNntid;

    if ($request['apiParams']['apiStatus'] == 'refund') {
        $nnApiParams['refund_request'] = 1;
        $nnApiParams['refund_param']   = $request['apiParams']['refundAmount'];

        if (!empty($request['apiParams']['refundRef']))
            $nnApiParams['refund_ref'] = $request['apiParams']['refundRef'];

        if ($request['apiParams']['refundMethod'] == 'nn_sepa') {
            $nnApiParams['account_holder'] = $request['apiParams']['accountHolderSepa'];
            $nnApiParams['iban']           = $request['apiParams']['accountNumberSepa'];
            $nnApiParams['bic']            = $request['apiParams']['bankCodeSepa'];
        }
    } elseif ($request['apiParams']['apiStatus'] == 'subsCancellation') {
        $nnApiParams['cancel_reason'] = $request['apiParams']['subsReason'];
        $nnApiParams['cancel_sub']    = 1;
    } else {
        $nnApiParams['status']      = 100;
        $nnApiParams['edit_status'] = 1;
        if ($request['apiParams']['apiStatus'] == 'amountUpdate') {
            $nnApiParams['update_inv_amount'] = 1;
            $nnApiParams['amount']            = $request['apiParams']['amount'];
            if (!empty($request['apiParams']['dueDateChange']))
                $nnApiParams['due_date']  = $request['apiParams']['dueDateChange'];
        } elseif ($request['apiParams']['apiStatus'] == 'void')
            $nnApiParams['status']    =  103;
    }
	//api call
    $data = $novalnetObj->sendCurlRequest($nnApiParams, 'https://payport.novalnet.de/paygate.jsp', $proxy);
    parse_str($data, $aryCaptResponse);

    //response for the api call
    $apiResponse = !empty($aryCaptResponse['status_desc']) ? $aryCaptResponse['status_desc'] : (!empty($aryCaptResponse['status_text']) ? $response['status_text'] : (!empty($aryCaptResponse['status_message']) ? $aryCaptResponse['status_message'] : 'Die Zahlung war nicht erfolgreich. Ein Fehler trat auf.'));

    if ($aryCaptResponse['status'] == 100) {
        if ($request['apiParams']['apiStatus'] == 'capture' || $request['apiParams']['apiStatus'] == 'void') {
            if ($request['apiParams']['apiStatus'] == 'capture') {
                $message = $apicallTexts['transaction_capture_text_1'] . date('d.m.Y') . $apicallTexts['transaction_capture_text_2'] . date('H:i:s') . $apicallTexts['transaction_capture_text_3'];
                $status = $oPlugin->oPluginEinstellungAssoc_arr['confirm_order_status'];

                if (!in_array($paymentMethod, array('novalnet_invoice', 'novalnet_prepayment'))) {
                    $jtlPaymentmethod = new PaymentMethod($orderObj->payment_id);
                    $incomingPayment = new stdClass();
                    $incomingPayment->fBetrag = $orderObj->fGesamtsumme;
                    $incomingPayment->cISO = $currency;
                    $incomingPayment->cHinweis = $nnApiParams['tid'];
                    $jtlPaymentmethod->name = $orderObj->cZahlungsartName;
                    $jtlPaymentmethod->addIncomingPayment( $orderObj, $incomingPayment ); // Adds the current transaction into the shop's order table

                    Shop::DB()->query('UPDATE tbestellung SET dBezahltDatum = now() WHERE cBestellNr = "' . $orderNo . '"',4);
                }

                $novalnetObj->setOrderStatus($orderNo, $status, true);
            } else {
                $message = $apicallTexts['transaction_void_text_1'] . date('d.m.Y') . $apicallTexts['transaction_void_text_2'] . date('H:i:s') . $apicallTexts['transaction_void_text_3'] ;
                $status = $oPlugin->oPluginEinstellungAssoc_arr['cancel_order_status'];

                $novalnetObj->setOrderStatus($orderNo, $status, false, true);
            }

            Shop::DB()->query("UPDATE xplugin_novalnetag_tnovalnet_status SET nStatuswert = ".$aryCaptResponse['tid_status']." , cKommentare = CONCAT(cKommentare, '" . $message . $nnBreak . "') , dDatum = CONCAT(dDatum, '" . date('d.m.Y H:i:s') . $nnBreak ."') WHERE cNnorderid ='".$orderNo."'", 1);

        } elseif ($request['apiParams']['apiStatus'] == 'amountUpdate') {
            if (in_array($paymentMethod, array('novalnet_invoice', 'novalnet_prepayment'))) {
                Shop::DB()->query("UPDATE xplugin_novalnetag_tpreinvoice_transaction_details SET nBetrag = " . ($nnApiParams['amount'] /100) . ", cRechnungDuedate = '" . $nnApiParams['due_date'] . "' , dDatum = '" . date('Y-m-d H:i:s') . "' WHERE cBestellnummer ='".$orderNo."'",1);

                $invoicePrepaymentDetails = array(
                  'invoice_bankname'  => $transDetails->cbankName,
                  'invoice_bankplace' => $transDetails->cbankCity,
                  'amount'            => ($nnApiParams['amount'] /100),
                  'currency'          => $currency,
                  'tid'               => $orderDetails->nNntid,
                  'invoice_iban'      => $transDetails->cbankIban,
                  'invoice_bic'       => $transDetails->cbankBic,
                  'due_date'          => $nnApiParams['due_date'],
                  'product_id'        => $productId,
                  'order_no'          => $orderNo,
                  'referenceValues'   => $transDetails->cReferenceValues
                  );

                $comments = PHP_EOL;
                if ($transDetails->bTestmodus) {
                    $comments .= $apicallTexts['test_order'] . PHP_EOL;
                }

                $comments .= $apicallTexts['tid_label'] . $orderDetails->nNntid . PHP_EOL;
                $comments .= $novalnetObj->formInvoicePrepaymentComments($invoicePrepaymentDetails, $currency, $paymentMethod, true);
            }
            $message = $apicallTexts['amount_update_message_1'] . number_format( gibPreisString($nnApiParams['amount']) / 100, 2, ',', '' ) .' '. ($currency) . $apicallTexts['amount_update_message_2'] . date('d.m.Y') . $apicallTexts['amount_update_message_3'] . date('H:i:s') . $apicallTexts['amount_update_message_4'];

            Shop::DB()->query("UPDATE xplugin_novalnetag_tnovalnet_status SET nStatuswert = ".$aryCaptResponse['tid_status']." , nBetrag = ".$nnApiParams['amount'].", cKommentare = CONCAT(cKommentare, '" . ( !empty($comments) ? $comments . PHP_EOL : '' ) . $message . $nnBreak . "') , dDatum = CONCAT(dDatum, '" . date('d.m.Y H:i:s') . $nnBreak ."') WHERE cNnorderid ='".$orderNo."'", 1);
        } elseif ($request['apiParams']['apiStatus'] == 'subsCancellation') {
            if ($request['apiParams']['frontEnd'])
                $apicallTexts['subscription_cancelled'] = ($orderObj->kSprache == 2) ? 'Subscription has been canceled due to:' : utf8_decode('Das Abonnement wurde gekündigt wegen:');

            $message = $apicallTexts['subscription_cancelled'] . ' ' . $request['apiParams']['subsReason'];

            $novalnetObj->setOrderStatus($orderNo, $oPlugin->oPluginEinstellungAssoc_arr['subscription_order_status']);

            Shop::DB()->query("UPDATE xplugin_novalnetag_tsubscription_details SET cTerminationReason = '".$request['apiParams']['subsReason']."', dTerminationAt = '".date('Y-m-d H:i:s')."' WHERE cBestellnummer = '".$orderNo."'", 1);

            Shop::DB()->query("UPDATE xplugin_novalnetag_tnovalnet_status SET cKommentare = CONCAT(cKommentare, '" . $message . $nnBreak ."') , dDatum = CONCAT(dDatum, '" . date('d.m.Y H:i:s') . $nnBreak ."'  ) WHERE cNnorderid ='".$orderNo."'", 1);
        } else {
            $refundAmount = $orderDetails->nErstattungsbetrages + $nnApiParams['refund_param'];
            $message = $apicallTexts['refund_message_1'] . $nnApiParams['tid'] . $apicallTexts['refund_message_2'] .number_format( gibPreisString($request['apiParams']['refundAmount']) / 100, 2, ',', '' ) .' '.$currency.' '.$apicallTexts['refund_message_3'].'.';

                if ($nnApiParams['key'] == 34 && isset($aryCaptResponse['paypal_refund_tid']) && !empty($aryCaptResponse['paypal_refund_tid'])) {
                    $message .= ' - PayPal Ref: ' . $aryCaptResponse['paypal_refund_tid']. '.';
                }

                if (isset($aryCaptResponse['tid']) && !empty($aryCaptResponse['tid'])) {
                    $message .= $apicallTexts['new_TID_message'] . $aryCaptResponse['tid'];
                }

                Shop::DB()->query("UPDATE xplugin_novalnetag_tnovalnet_status SET nStatuswert = ".$aryCaptResponse['tid_status'].", nErstattungsbetrages = " . $refundAmount . ", cKommentare = CONCAT(cKommentare, '" . $message .  $nnBreak . "') , dDatum = CONCAT(dDatum, '" . date('d.m.Y H:i:s') . $nnBreak . "' ) WHERE cNnorderid = '".$orderNo."'", 1);

            if ($aryCaptResponse['tid_status'] == 103) {
                $novalnetObj->setOrderStatus($orderNo, $oPlugin->oPluginEinstellungAssoc_arr['cancel_order_status'], false, true);
            }
        }

        Shop::DB()->query("UPDATE tbestellung SET cKommentar = CONCAT(cKommentar, '" . ( !empty($comments) ? PHP_EOL . $comments : '' ) . PHP_EOL . $message . PHP_EOL . "') WHERE cBestellNr ='".$orderNo."'", 1);

    } else {
       $message = utf8_decode(in_array($request['apiParams']['apiStatus'], array('capture','void')) ? ($apiResponse . 'Status : '.$aryCaptResponse['status']) : $apiResponse);
    }
    echo $message;
    exit;
}
