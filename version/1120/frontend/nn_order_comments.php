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
 * Script: nn_order_comments.php
 */

if (strpos($_SESSION['Zahlungsart']->cModulId, 'novalnet') !== false
        && !empty($_SESSION['nn_comments'])) {
  // Set the Novalnet transaction comments in Order object and Replace the dummy variable with the original order number
    $comments = str_replace('NN_ORDER', $args_arr['oBestellung']->cBestellNr, $_SESSION['nn_comments']);
    $args_arr['oBestellung']->cKommentar = $comments;
}
