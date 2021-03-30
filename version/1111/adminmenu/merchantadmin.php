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
 * Script: merchantadmin.php
 *
 */
global $oPlugin, $smarty, $shopUrl;

$smarty->assign('adminPathDir', $shopUrl . '/' . PFAD_PLUGIN . $oPlugin->cVerzeichnis . '/' . PFAD_PLUGIN_VERSION . $oPlugin->nVersion . '/' . PFAD_PLUGIN_ADMINMENU);

// Loads Novalnet merchant administration portal template
print $smarty->fetch($oPlugin->cAdminmenuPfad . 'template/merchantadmin.tpl');
