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
 * Script: nn_mail_subject.php
 */
$conf = Shop::getSettings(array(CONF_GLOBAL));
if ($args_arr['Emailvorlage']->cModulId == 'novalnetcallbackmail'
        || $args_arr['Emailvorlage']->cModulId == 'novalnetnotification') {
    //Update Store name in mail subject in the Callback Script Execution
    $args_arr['mail']->subject = !empty($conf['global']['global_shopname'])
            ? $args_arr['mail']->subject.' - '.$conf['global']['global_shopname']
            : $args_arr['mail']->subject.' - '.'JTLSHOP';
}
