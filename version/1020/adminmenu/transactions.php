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
 * Script : transactions.php
 *
 */

header('Access-Control-Allow-Origin: *');

//request
$request = $_REQUEST;

require_once($request['pluginInc']);

//get plugin object
$oPlugin = Plugin::getPluginById('novalnetag');

include_once( PFAD_ROOT . PFAD_PLUGIN . $oPlugin->cPluginID . '/' . PFAD_PLUGIN_VERSION . $oPlugin->nVersion . '/' . PFAD_PLUGIN_PAYMENTMETHOD . PFAD_CLASSES . 'class.Novalnet.php');

//get order details
$orderInfo = Shop::DB()->query("SELECT nStatuswert, cZahlungsmethode, nBetrag, SUM(nErstattungsbetrages) AS refAmount FROM xplugin_novalnetag_tnovalnet_status WHERE cNnorderid = '".$request['orderNo']."'",1);

//get customer details for the order from order table
$customerDetails = Shop::DB()->query("SELECT fGesamtsumme, kKunde, cKommentar, dErstellt FROM tbestellung WHERE cBestellNr = '".$request['orderNo']."'",1);

//get subscription details
$subscriptionInfo = Shop::DB()->query("SELECT nSubsId, cTerminationReason FROM xplugin_novalnetag_tsubscription_details WHERE cBestellnummer = '".$request['orderNo']."'",1);

//get callback log details
$callbackInfo = Shop::DB()->query("SELECT SUM(nCallbackAmount) AS totalAmount FROM xplugin_novalnetag_tcallback WHERE cBestellnummer ='".$request['orderNo']."'",1);

//get invoice payments details
$invoiceInfo = Shop::DB()->query("SELECT cRechnungDuedate FROM  xplugin_novalnetag_tpreinvoice_transaction_details WHERE cBestellnummer ='".$request['orderNo']."'",1);

Shop::Smarty()->assign( array(
    'customerDetails' => $customerDetails,
    'orderInfo'       => $orderInfo,
    'currency'        => NovalnetGateway::getPaymentCurrency( $request['orderNo'] ), // Get currency type for the current order
    'invoiceInfo'     => $invoiceInfo,
    'callbackInfo'    => $callbackInfo,
    'nnOrderno'       => $request['orderNo'],
    'invoicePayments' => array('novalnet_invoice', 'novalnet_prepayment'),
    'subscriptionInfo'=> $subscriptionInfo,
    'customerInfo'    => new Kunde( $customerDetails->kKunde ),
    'oPlugin'         => $oPlugin
) );
// Loads Novalnet extensions template
print Shop::Smarty()->display( $oPlugin->cAdminmenuPfad . 'template/transactions.tpl' );
