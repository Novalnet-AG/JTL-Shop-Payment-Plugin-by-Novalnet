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
 * Script: novalnetupdate.php
 *
 */
 
global $oPlugin, $smarty, $shopUrl;

$smarty->assign(array(
    'pluginUrl'   => $shopUrl . '/' . PFAD_PLUGIN . $oPlugin->cVerzeichnis . '/' . PFAD_PLUGIN_VERSION . $oPlugin->nVersion . '/'
));

// Loads info template to display Novalnet update information
print $smarty->display($oPlugin->cAdminmenuPfad . 'template/novalnetupdate.tpl');
