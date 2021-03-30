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
 * Script : nn_mail.php
 *
 */
$oOrder = new Bestellung( $_SESSION['kBestellung'] );

$oOrder->cKommentar = str_replace( 'NN_ORDER', $oOrder->cBestellNr, $oOrder->cKommentar );

$args_arr['mail']->bodyText = str_replace( 'NN_ORDER', $oOrder->cBestellNr, $args_arr['mail']->bodyText );
$args_arr['mail']->bodyHtml = str_replace( 'NN_ORDER', $oOrder->cBestellNr, $args_arr['mail']->bodyHtml );
