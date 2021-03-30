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
 * Script: nn_orderstatus.php
 */
 
if(!empty($args_arr['oBestellung']->kBestellung) && strpos($_SESSION['Zahlungsart']->cModulId, 'novalnet') !== false) {
	Shop::DB()->query('UPDATE tbestellung SET cAbgeholt = "Y" WHERE kBestellung="' . $args_arr['oBestellung']->kBestellung . '"', 4);
}
