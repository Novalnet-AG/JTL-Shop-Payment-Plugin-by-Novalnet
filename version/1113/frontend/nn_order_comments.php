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
 * Script: nn_order_comments.php
 */

if (strpos($_SESSION['Zahlungsart']->cModulId, 'novalnet') !== false && !empty($_SESSION['nn_comments'])) {
	// Set the Novalnet transaction comments in Order object and Replace the dummy variable with the original order number
	$args_arr['oBestellung']->cKommentar = str_replace('NN_ORDER', $args_arr['oBestellung']->cBestellNr, $_SESSION['nn_comments']);
}
