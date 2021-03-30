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
 * Script : info.php
 *
 */
global $oPlugin;

Shop::Smarty()->assign( array(
    'NN_URL_PATH', Shop::getURL(),
    'url_path' => Shop::getURL() . '/' . PFAD_PLUGIN . $oPlugin->cVerzeichnis . '/' . PFAD_PLUGIN_VERSION . $oPlugin->nVersion . '/' . PFAD_PLUGIN_PAYMENTMETHOD . 'img/logo.png',
    'callback_url' => empty($oPlugin->oPluginEinstellungAssoc_arr['callback_url']) ? Shop::getURL() . '/?novalnet_callback' : $oPlugin->oPluginEinstellungAssoc_arr['callback_url'],
    'pluginUrl' => Shop::getURL() . '/' . PFAD_PLUGIN . $oPlugin->cVerzeichnis . '/' . PFAD_PLUGIN_VERSION . $oPlugin->nVersion. '/' ,
));

print Shop::Smarty()->fetch( $oPlugin->cAdminmenuPfad . 'template/info.tpl' );
