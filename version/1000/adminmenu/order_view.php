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
 * Script : order_view.php
 *
 */

global $oPlugin, $smarty, $DB;

require_once( 'includes/admininclude.php' );
require_once( PFAD_ROOT . PFAD_ADMIN . PFAD_INCLUDES . 'blaetternavi.php' );
require_once( PFAD_ROOT . PFAD_CLASSES . 'class.JTL-Shop.Bestellung.php' );
require_once( PFAD_ROOT . PFAD_ADMIN . PFAD_INCLUDES . 'bestellungen_inc.php' );

// navigation
$nAnzahlProSeite = 10;
$oBlaetterNaviConf = baueBlaetterNaviGetterSetter(1, $nAnzahlProSeite);

// orders
$nAnzahlBestellungen = $DB->executeQuery("SELECT cNnorderid FROM xplugin_novalnetag_tnovalnet_status", 3);

$oBestellungArr = $DB->executeQuery("SELECT DISTINCT ord.kBestellung FROM tbestellung ord JOIN xplugin_novalnetag_tnovalnet_status nov WHERE ord.cBestellNr = nov.cNnorderid ORDER BY ord.kBestellung DESC {$oBlaetterNaviConf->cSQL1}", 2);

// fill
foreach ($oBestellungArr as &$oBestellung) {
	$oBestellung = new Bestellung($oBestellung->kBestellung);
	$oBestellung->fuelleBestellung(1, 0);
}

// navigation
$oBlaetterNaviUebersicht = baueBlaetterNavi($oBlaetterNaviConf->nAktuelleSeite1, $nAnzahlBestellungen, $nAnzahlProSeite);
$status =  array('5' => 'teilversendet','4' => 'versendet', '3' => 'bezahlt', '2' => 'in Bearbeitung' , '1' => 'offen' , '-1' => 'Storno');
$smarty->assign( array (
				'oBestellung_arr' 	 	  => $oBestellungArr,
				'oBestellung_status' 	  => $status,
				'oBlaetterNaviUebersicht' => $oBlaetterNaviUebersicht,
				'ordersPathDir'			  => gibShopURL() . '/' . PFAD_PLUGIN . $oPlugin->cVerzeichnis . '/' . PFAD_PLUGIN_VERSION . $oPlugin->nVersion . '/' . PFAD_PLUGIN_PAYMENTMETHOD));

	print $smarty->fetch( $oPlugin->cAdminmenuPfad . 'template/order_view.tpl' );
?>
