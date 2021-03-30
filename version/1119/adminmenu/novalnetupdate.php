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
 * Script: novalnetupdate.php
 *
 */
 
global $oPlugin, $smarty, $shopUrl;

$smarty->assign(array(
    'pluginUrl'   => $shopUrl . '/' . PFAD_PLUGIN . $oPlugin->cVerzeichnis . '/' . PFAD_PLUGIN_VERSION . $oPlugin->nVersion . '/'
));

// Loads info template to display Novalnet update information
print $smarty->display($oPlugin->cAdminmenuPfad . 'template/novalnetupdate.tpl');
