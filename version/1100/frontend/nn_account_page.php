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
 * Script: nn_account_page.php
 */

// Make Novalnet transaction comments aligned in My Account page of the user

Shop::Smarty()->tpl_vars['Bestellung']->value->cKommentar = nl2br( Shop::Smarty()->tpl_vars['Bestellung']->value->cKommentar );
