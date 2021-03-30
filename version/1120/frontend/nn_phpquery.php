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
 * Script: nn_phpquery.php
 *
 */

// PHP selector query to handle Novalnet Creditcard logos display in user-end

require_once(PFAD_ROOT . PFAD_PLUGIN . $oPlugin->cPluginID . '/' . PFAD_PLUGIN_VERSION . $oPlugin->nVersion . '/'
. PFAD_PLUGIN_PAYMENTMETHOD . PFAD_CLASSES . 'Novalnet.helper.class.php');

global $smarty, $shopUrl, $shopVersion;

$pluginPath = $shopUrl . '/' . PFAD_PLUGIN . $oPlugin->cVerzeichnis . '/' . PFAD_PLUGIN_VERSION . $oPlugin->nVersion;

// Request
$request = $_REQUEST;

if (gibSeitenTyp()==PAGE_BESTELLVORGANG) { // Check if the page is a payment page only
    global $step;

    if (in_array($step, array('Zahlung', 'Versand'))) {
        $nnLogos['master'] = 1;

        $nnCreditcardLogos = array('amex', 'maestro', 'cartasi');

        foreach ($nnCreditcardLogos as $val) {
            $nnLogos[$val] = $oPlugin->oPluginEinstellungAssoc_arr['cc_'. $val .'_accept'];
        }

        $nnLogos = array_filter($nnLogos);

        if (!empty($nnLogos)) {
            foreach (($shopVersion == '4x') ? $smarty->tpl_vars['Zahlungsarten']->value :
            $smarty->_tpl_vars['Zahlungsarten'] as $payment) {
                if (strpos($payment->cModulId, 'novalnetkreditkarte')) {
                    foreach ($nnLogos as $logos => $value) {
                        $ccLogo[$logos] = $pluginPath . '/paymentmethod/img/novalnet_cc_' . $logos . '.png';
                    }

                    $logoQuery = http_build_query($ccLogo, '', '&');
                    $paymentLogoAlt = $payment->angezeigterName[$_SESSION{'cISOSprache'}];
                    $shopElement = $shopVersion == '4x' ? '.radio' : 'div';
                    $lowerShopSeries = strpos(getShopTemplate(), 'Mobil') !== false && $shopVersion == '3x';
                    $isShopHigherVersion = JTL_VERSION >= 406;

                    $nnScriptHead = <<<HTML
<input type='hidden' id='nn_logo_alt' value='{$paymentLogoAlt}'>
<input type='hidden' id='nn_payment' value='{$payment->cModulId}'>
<input type='hidden' id='nn_logos' value='{$logoQuery}'>
<input type='hidden' id='nn_shop_element' value='{$shopElement}'>
<input type='hidden' id='nn_payment_no' value='{$payment->kZahlungsart}'>
<input type='hidden' id='nn_mobile_version' value='{$lowerShopSeries}'>
<input type='hidden' id='nn_shop_higher_version' value='{$isShopHigherVersion}'>
<script type='text/javascript' src='{$pluginPath}/frontend/js/novalnet_logo.js'></script>
HTML;
                    pq('head')->append($nnScriptHead); // PQ selector usage to append script
                }
            }
        }
    }

    $nnScriptHead = <<<HTML
    <script type='text/javascript' src='{$pluginPath}/frontend/js/novalnet_complete_order.js'></script>
HTML;
    pq('head')->append($nnScriptHead); // PQ selector usage to append script

} elseif (gibSeitenTyp()==PAGE_BESTELLABSCHLUSS) { // Check if the page is a checkout page only
    if (!empty($_SESSION['kBestellung'])) {
		$order = new Bestellung($_SESSION['kBestellung']);
        // Loading the payment module, so as to execute to perform the script loading for Novalnet cashpayment only.
        $paymentModule = nnGetPaymentModuleId($order->kZahlungsart);

        // Verifies if the cashpayment token is set and loads the slip from Barzahlen accordingly.
        if ($paymentModule &&
            strpos($paymentModule, 'novalnetbarzahlen') !== false
            && !empty($_SESSION['novalnet_cashpayment_token'])) {
            $slipUrl = !empty($_SESSION['novalnet_cashpayment_mode'])
            ? 'https://cdn.barzahlen.de/js/v2/checkout-sandbox.js'
            : 'https://cdn.barzahlen.de/js/v2/checkout.js';

            pq('body')->append('<script src="'. $slipUrl . '"
                                        class="bz-checkout"
                                        data-token="'. $_SESSION['novalnet_cashpayment_token'] . '"
                                        data-auto-display="true">
                                </script>
                                <style type="text/css">
                                    #bz-checkout-modal { position: fixed !important; }
                                </style>');

            pq('#order-confirmation')->append('<a href="javascript:bzCheckout.display();">
                                           ' . $oPlugin->oPluginSprachvariableAssoc_arr['__NN_cashpayment_slipurl'] . '
                                              </a>');

            unset($_SESSION['novalnet_cashpayment_token']);
            unset($_SESSION['novalnet_cashpayment_mode']);
        }
    }
}
