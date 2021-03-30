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
 * Script: info.php
 *
 */
global $oPlugin, $smarty, $shopUrl, $shopVersion;

require_once(PFAD_ROOT . PFAD_PLUGIN . $oPlugin->cPluginID . '/' . PFAD_PLUGIN_VERSION . $oPlugin->nVersion . '/' . PFAD_PLUGIN_PAYMENTMETHOD . PFAD_CLASSES . 'Novalnet.helper.class.php');

// Creates instance for the NovalnetHelper class
$helper = new NovalnetHelper();

// Updates the core table
$helper->UpdatePluginId();

$smarty->assign(array(
    'shopUrl'     => $shopUrl,
    'pluginUrl'   => $shopUrl . '/' . PFAD_PLUGIN . $oPlugin->cVerzeichnis . '/' . PFAD_PLUGIN_VERSION . $oPlugin->nVersion . '/',
    'shopVersion' => $shopVersion,
    'language'    => ($GLOBALS['oSprache']->cISOSprache == 'ger' ? 'DE' : 'EN'),
    'pluginInc'	  => PFAD_ROOT . PFAD_INCLUDES . 'globalinclude.php',
    'callbackUrl' => empty($oPlugin->oPluginEinstellungAssoc_arr['callback_url'])
                        ? $shopUrl . '/?novalnet_callback'
                        : $oPlugin->oPluginEinstellungAssoc_arr['callback_url']
));

// Loads info template to display information about Novalnet
print $smarty->display($oPlugin->cAdminmenuPfad . 'template/info.tpl');
