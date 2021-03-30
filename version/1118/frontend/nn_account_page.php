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
