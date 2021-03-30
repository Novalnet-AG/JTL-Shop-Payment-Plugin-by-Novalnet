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
 * Script : nn_request_handler.php
 *
 */

// Condition to handle affiliate process
if (isset($_GET['nn_aff_id']) && is_numeric($_REQUEST['nn_aff_id'])) {
    $_SESSION['nn_aff_id'] = $_GET['nn_aff_id'];
}

// Condition to handle callback executions
if (isset($_REQUEST['novalnet_callback'])) {
    require_once( PFAD_ROOT . PFAD_PLUGIN . $oPlugin->cPluginID . '/' . PFAD_PLUGIN_VERSION . $oPlugin->nVersion . '/lib/Novalnet.callback.class.php' );
    performCallbackExecution(); // Handles callback request
}
