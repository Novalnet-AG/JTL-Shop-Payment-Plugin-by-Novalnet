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
 * Script: nn_account_page.php
 */

// Makes Novalnet transaction comments aligned in My Account page of the user
require_once(PFAD_ROOT . PFAD_PLUGIN . $oPlugin->cPluginID . '/' . PFAD_PLUGIN_VERSION . $oPlugin->nVersion . '/'
. PFAD_PLUGIN_PAYMENTMETHOD . PFAD_CLASSES . 'Novalnet.helper.class.php');

global $smarty, $shopVersion;

if ($oPlugin->oPluginEinstellungAssoc_arr['display_order_comments'] == '1') {
    if ($shopVersion == '3x') {
        $smarty->_tpl_vars['Bestellung']->cKommentar = nl2br($smarty->_tpl_vars['Bestellung']->cKommentar);
    } else {
        if (!empty($smarty->tpl_vars['Bestellung'])) {
            $smarty->tpl_vars['Bestellung']->value->cKommentar = nl2br($smarty->tpl_vars['Bestellung']->value->cKommentar);
        }
    }
}
