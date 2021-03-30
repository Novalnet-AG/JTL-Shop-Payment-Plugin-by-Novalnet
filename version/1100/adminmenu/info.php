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
global $oPlugin;
 
Shop::Smarty()->assign( array(
	'shopUrl'     => Shop::getURL(),
	'pluginUrl'   => Shop::getURL() . '/' . PFAD_PLUGIN . $oPlugin->cVerzeichnis . '/' . PFAD_PLUGIN_VERSION . $oPlugin->nVersion . '/',
	'systemIp'	  => $_SERVER['SERVER_ADDR'] == '::1' ? '127.0.0.1' : $_SERVER['SERVER_ADDR'],
	'callbackUrl' => empty ( $oPlugin->oPluginEinstellungAssoc_arr['callback_url'] ) ? Shop::getURL() . '/?novalnet_callback' : $oPlugin->oPluginEinstellungAssoc_arr['callback_url']
) );

// Loads info template to display information about Novalnet
print Shop::Smarty()->display( $oPlugin->cAdminmenuPfad . 'template/info.tpl' );
