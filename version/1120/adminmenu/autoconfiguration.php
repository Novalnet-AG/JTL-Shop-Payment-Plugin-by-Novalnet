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
 * Script: autoconfiguration.php
 *
 */
 
if(!empty($_REQUEST)) {
// Request
$request = $_REQUEST;

require_once($request['pluginInc']);

// Get plugin object
$oPlugin = Plugin::getPluginById('novalnetag');

require_once(PFAD_ROOT . PFAD_PLUGIN . $oPlugin->cPluginID . '/' . PFAD_PLUGIN_VERSION . $oPlugin->nVersion . '/' . PFAD_PLUGIN_PAYMENTMETHOD . PFAD_CLASSES . 'NovalnetGateway.class.php');

//Get Novalnet gateway class instance

$novalnetGateway = NovalnetGateway::getInstance();

$autoConfigRequestParams= array(
                                'hash'  =>  $request['api_config_hash'],
                                'lang'              =>  'DE'
                                );

$transactionResponse = http_get_contents($novalnetGateway->novalnetAutoConfigUrl, $novalnetGateway->getGatewayTimeout(),$autoConfigRequestParams);

echo $transactionResponse;
}

