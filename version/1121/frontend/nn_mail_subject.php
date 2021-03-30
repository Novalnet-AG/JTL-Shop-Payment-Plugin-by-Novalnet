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
