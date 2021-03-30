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
 * Script: info.php
 *
 */
global $oPlugin, $smarty, $shopUrl, $shopVersion;

require_once(PFAD_ROOT . PFAD_PLUGIN . $oPlugin->cPluginID . '/' . PFAD_PLUGIN_VERSION . $oPlugin->nVersion . '/' . PFAD_PLUGIN_PAYMENTMETHOD . PFAD_CLASSES . 'Novalnet.helper.class.php');

$smarty->assign( array(
    'shopUrl'     => $shopUrl,
    'pluginUrl'   => $shopUrl . '/' . PFAD_PLUGIN . $oPlugin->cVerzeichnis . '/' . PFAD_PLUGIN_VERSION . $oPlugin->nVersion . '/',
    'systemIp'    => nnGetIpAddress('SERVER_ADDR'),
    'shopVersion' => $shopVersion,
    'callbackUrl' => empty($oPlugin->oPluginEinstellungAssoc_arr['callback_url']) ? $shopUrl . '/?novalnet_callback' : $oPlugin->oPluginEinstellungAssoc_arr['callback_url']
) );

// Loads info template to display information about Novalnet
print $smarty->display($oPlugin->cAdminmenuPfad . 'template/info.tpl');
