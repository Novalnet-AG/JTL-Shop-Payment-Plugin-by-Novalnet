<?php
/**
 * Novalnet payment method module
 * This module is used for real time processing of
 * Novalnet transaction of customers.
 *
 * Copyright (c) Novalnet AG
 *
 * Released under the GNU General Public License
 * This free contribution made by request.
 * If you have found this script useful a small
 * recommendation as well as a comment on merchant form
 * would be greatly appreciated.
 *
 * Script: transactions.php
 *
 */
header('Access-Control-Allow-Origin: *');

// Request
$request = $_REQUEST;

require_once( $request['pluginInc'] );

// Get plugin object
$oPlugin = Plugin::getPluginById( 'novalnetag' );

require_once( PFAD_ROOT . PFAD_PLUGIN . $oPlugin->cPluginID . '/' . PFAD_PLUGIN_VERSION . $oPlugin->nVersion . '/' . PFAD_PLUGIN_PAYMENTMETHOD . PFAD_CLASSES . 'Novalnet.abstract.class.php' );

// Get order details from Novalnet table
$orderInfo = Shop::DB()->query( 'SELECT nNntid, cZahlungsmethode, nBetrag, nStatuswert FROM xplugin_novalnetag_tnovalnet_status WHERE cNnorderid = "' . $request['orderNo'] . '"', 1);

// Get customer details for the order from shop's order table
$customerDetails = Shop::DB()->query( 'SELECT fGesamtsumme, kKunde, cKommentar, dErstellt FROM tbestellung WHERE cBestellNr = "' . $request['orderNo'] . '"', 1);

// Get subscription details from Novalnet subscription table
$subscriptionInfo = Shop::DB()->query( 'SELECT nSubsId, cTerminationReason FROM xplugin_novalnetag_tsubscription_details WHERE cBestellnummer = "' . $request['orderNo'] . '"', 1);

// Get Invoice payments details from Novalnet invoice table
$invoiceInfo = Shop::DB()->query( 'SELECT cRechnungDuedate FROM  xplugin_novalnetag_tpreinvoice_transaction_details WHERE cBestellnummer ="' . $request['orderNo'] . '"', 1);

// Get Callback log details Novalnet callback table
$callbackInfo = Shop::DB()->query( 'SELECT SUM(nCallbackAmount) AS totalAmount FROM xplugin_novalnetag_tcallback WHERE cBestellnummer ="' . $request['orderNo'] . '"', 1);

Shop::Smarty()->assign( array(
	'customerDetails' => $customerDetails,
	'orderInfo'		  => $orderInfo,
	'currency'		  => NovalnetGateway::getPaymentCurrency( $request['orderNo'] ), // Get currency type for the current order
	'invoiceInfo'	  => $invoiceInfo,
	'callbackInfo'	  => $callbackInfo,
	'nnOrderno'		  => $request['orderNo'],
	'apiCode'		  => array( 'debit' => 'capture', 'cancel' => 'void', 'refund' => 'refund' , 'amountChange' => 'amountUpdate', 'subscription' => 'subsCancellation', 'book' => 'zeroBooking' ),
	'invoicePayments' => array( 'novalnet_invoice', 'novalnet_prepayment' ),
	'subscriptionInfo'=> $subscriptionInfo,
	'customerInfo'    => new Kunde( $customerDetails->kKunde ),
	'oPlugin'		  => $oPlugin
) );

// Loads Novalnet extensions template
print Shop::Smarty()->fetch( $oPlugin->cAdminmenuPfad . 'template/transactions.tpl' );
