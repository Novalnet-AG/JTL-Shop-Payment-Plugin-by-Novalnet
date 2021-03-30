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
 * Script: orders.php
 *
 */
global $oPlugin, $smarty, $DB, $shopUrl, $shopQuery, $shopVersion;

require_once(PFAD_ROOT . PFAD_ADMIN . PFAD_INCLUDES . 'blaetternavi.php');
require_once(PFAD_ROOT . PFAD_CLASSES . 'class.JTL-Shop.Bestellung.php');
require_once(PFAD_ROOT . PFAD_ADMIN . PFAD_INCLUDES . 'bestellungen_inc.php');

/************** Novalnet Orders fetch *********************/

$nAnzahlProSeite = 10; // Number of Novalnet orders per page

$oBlaetterNaviConf = baueBlaetterNaviGetterSetter(1, $nAnzahlProSeite); // Core function - Getter, setter to configure the number of orders to get displayed per page

// Retrieves the Novalnet orders stored in database
$nAnzahlBestellungen = $DB->$shopQuery('SELECT cNnorderid FROM xplugin_novalnetag_tnovalnet_status', 3);

$oBestellungArr = $DB->$shopQuery('SELECT DISTINCT ord.kBestellung FROM tbestellung ord JOIN xplugin_novalnetag_tnovalnet_status nov WHERE ord.cBestellNr = nov.cNnorderid ORDER BY ord.kBestellung DESC ' . $oBlaetterNaviConf->cSQL1, 2);

// Assigns and fills the order with its corresponding values
foreach ($oBestellungArr as &$oBestellung) {
    $oBestellung = new Bestellung($oBestellung->kBestellung);
    $oBestellung->fuelleBestellung(1, 0); // Core function - To load order
}

$smarty->assign(array(
    'oBestellung_arr'         => $oBestellungArr,
    'oBestellung_status'      => array( '5' => 'teilversendet','4' => 'versendet', '3' => 'bezahlt', '2' => 'in Bearbeitung' , '1' => 'offen' , '-1' => 'Storno' ),
    'oBlaetterNaviUebersicht' => baueBlaetterNavi($oBlaetterNaviConf->nAktuelleSeite1, $nAnzahlBestellungen, $nAnzahlProSeite), // Core function - To load orders in pages
    'adminPathDir'            => $shopUrl . '/' . PFAD_PLUGIN . $oPlugin->cVerzeichnis . '/' . PFAD_PLUGIN_VERSION . $oPlugin->nVersion . '/' . PFAD_PLUGIN_ADMINMENU,
    'pluginInclude'           => PFAD_ROOT . PFAD_INCLUDES . 'globalinclude.php'
));

// Loads template which displays Novalnet orders in pages
print $smarty->fetch($oPlugin->cAdminmenuPfad . 'template/' . $shopVersion . '/orders.tpl');
