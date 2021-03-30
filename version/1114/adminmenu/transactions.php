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
 * Script: transactions.php
 *
 */
header('Access-Control-Allow-Origin: *');
// Request
$request = $_REQUEST;

require_once($request['pluginInc']);

global $smarty, $DB, $shopQuery, $shopVersion;

// Get plugin object
$oPlugin = Plugin::getPluginById('novalnetag');

require_once(PFAD_ROOT . PFAD_PLUGIN . $oPlugin->cPluginID . '/' . PFAD_PLUGIN_VERSION . $oPlugin->nVersion . '/' . PFAD_PLUGIN_PAYMENTMETHOD . PFAD_CLASSES . 'NovalnetGateway.class.php');

$transactionTemplate = $oPlugin->cAdminmenuPfad . 'template/' . $shopVersion . '/transactions.tpl';

// Checks shop version and compiles the transaction template for the lower shop versions
if ($shopVersion == '3x') {

    require_once(PFAD_ROOT . PFAD_SMARTY . 'Smarty.class.php');
    // Creates Smarty Instance for the lower shop series
    $smarty = new Smarty;
    $smarty->compile_dir = PFAD_ROOT . PFAD_ADMIN . PFAD_COMPILEDIR;

    $_compile_directory = $smarty->_get_compile_path($transactionTemplate);
    // Compiles the transactions template file for displaying it later
    $smarty->_compile_resource($transactionTemplate, $_compile_directory);
}

// Get order details for the given order from Novalnet table
$orderDetails = $DB->$shopQuery('SELECT ord.fGesamtsumme, ord.kKunde, ord.cKommentar, ord.dErstellt, nov.cKonfigurations, nov.cZahlungsmethode, nov.nBetrag, nov.nStatuswert, nov.cAdditionalInfo  FROM tbestellung ord JOIN xplugin_novalnetag_tnovalnet_status nov ON ord.cBestellNr = nov.cNnorderid WHERE cNnorderid = "' . $request['orderNo'] . '"', 1);

$paymentConfiguration = unserialize($orderDetails->cKonfigurations);

$additionalConfiguration = unserialize($orderDetails->cAdditionalInfo);

$smarty->assign( array(
    // Get order details from Novalnet table
    'orderInfo'         => $orderDetails,
    // Get currency type for the current order
    'currency'          => NovalnetGateway::getPaymentCurrency($request['orderNo']),
    // Get Invoice payments details from Novalnet invoice table
    'invoiceInfo'       => $DB->$shopQuery('SELECT cRechnungDuedate FROM xplugin_novalnetag_tpreinvoice_transaction_details WHERE cBestellnummer ="' . $request['orderNo'] . '"', 1),
    'cashPaymentExpiry' => !empty($additionalConfiguration['cashpaymentSlipduedate']) ? $additionalConfiguration['cashpaymentSlipduedate'] : '',
    // Get Callback log details Novalnet callback table
    'callbackInfo'      => $DB->$shopQuery('SELECT SUM(nCallbackAmount) AS kCallbackAmount FROM xplugin_novalnetag_tcallback WHERE cBestellnummer ="' . $request['orderNo'] . '"', 1),
    'nnOrderno'         => $request['orderNo'],
    // Assign arrays to smarty for usage
    'invoicePayments'   => array('novalnet_invoice', 'novalnet_prepayment'),
    'amtUpdateValid'    => in_array($paymentConfiguration['key'], array( '27', '37', '59' )),
    'onHoldStatus'      => array( '85', '91', '98', '99' ),
    'refundOptions'     => array( 'novalnet_invoice', 'novalnet_prepayment', 'novalnet_ideal', 'novalnet_banktransfer', 'novalnet_eps', 'novalnet_giropay' ),
    'subsReason'        => array( 'Angebot zu teuer', 'Betrug', '(Ehe-)Partner hat Einspruch eingelegt', 'Finanzielle Schwierigkeiten', 'Inhalt entsprach nicht meinen Vorstellungen', 'Inhalte nicht ausreichend', 'Nur an Probezugang interessiert', 'Seite zu langsam', 'Zufriedener Kunde', 'Zugangsprobleme', 'Sonstige' ),
    // Get subscription details from Novalnet subscription table
    'subscriptionInfo'  => $DB->$shopQuery('SELECT nSubsId, cTerminationReason FROM xplugin_novalnetag_tsubscription_details WHERE cBestellnummer = "' . $request['orderNo'] . '"', 1),
    'customerInfo'      => new Kunde($orderDetails->kKunde),
    'oPlugin'           => $oPlugin
) );

// Loads Novalnet extensions template
print $smarty->fetch($transactionTemplate);
