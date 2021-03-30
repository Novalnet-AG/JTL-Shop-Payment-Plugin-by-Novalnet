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
 * Script: nn_request_handler.php
 *
 */

// Condition to handle affiliate process
if (isset($_REQUEST['nn_aff_id']) && !filter_input(INPUT_GET, "nn_aff_id", FILTER_VALIDATE_INT) === false) {
    $_SESSION['nn_aff_id'] = trim($_REQUEST['nn_aff_id']);
}

// Condition to handle callback executions
if (isset($_REQUEST['novalnet_callback'])) {
    require_once($oPlugin->cFrontendPfad . 'inc/Novalnet.callback.class.php');
    performCallbackExecution(); // Handles callback request
}
