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
 * Script: nn_orderstatus.php
 */
 
if(!empty($args_arr['oBestellung']->kBestellung) && strpos($_SESSION['Zahlungsart']->cModulId, 'novalnet') !== false) {
	Shop::DB()->query('UPDATE tbestellung SET cAbgeholt = "Y" WHERE kBestellung="' . $args_arr['oBestellung']->kBestellung . '"', 4);
}
