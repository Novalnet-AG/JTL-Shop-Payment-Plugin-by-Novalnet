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
 * Script: nn_mail_comments.php
 *
 */

// Replace dummy order number with the original in the email order comments just before sending email

if (!empty($_SESSION['kBestellung'])) {

    $oOrder = new Bestellung($_SESSION['kBestellung']); // Loads order instance

    $oOrder->cKommentar = str_replace('NN_ORDER', $oOrder->cBestellNr, $oOrder->cKommentar);

    $args_arr['mail']->bodyText = str_replace('NN_ORDER', $oOrder->cBestellNr, $args_arr['mail']->bodyText);
    $args_arr['mail']->bodyHtml = str_replace('NN_ORDER', $oOrder->cBestellNr, $args_arr['mail']->bodyHtml);
}
