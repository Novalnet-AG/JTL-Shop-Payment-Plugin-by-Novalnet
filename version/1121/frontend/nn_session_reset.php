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
 * Script: nn_session_reset.php
 *
 */
$nn_sid = !empty($_POST['inputval1']) ? $_POST['inputval1'] : (!empty($_POST['nn_sid']) ? $_POST['nn_sid'] : '');
if (empty($_COOKIE['JTLSHOP']) && !empty($nn_sid)) {
    session_id($nn_sid);
}
