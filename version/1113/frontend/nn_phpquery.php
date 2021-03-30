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
 * Script: nn_php_query.php
 *
 */

// PHP selector query to handle Novalnet Creditcard logos display and Novalnet subscription management in user-end

require_once(PFAD_ROOT . PFAD_PLUGIN . $oPlugin->cPluginID . '/' . PFAD_PLUGIN_VERSION . $oPlugin->nVersion . '/' . PFAD_PLUGIN_PAYMENTMETHOD . PFAD_CLASSES . 'Novalnet.helper.class.php');

global $smarty, $shopUrl, $shopVersion;

$pluginPath = $shopUrl . '/' . PFAD_PLUGIN . $oPlugin->cVerzeichnis . '/' . PFAD_PLUGIN_VERSION . $oPlugin->nVersion;

// Request
$request = $_REQUEST;

if (!empty($request['bestellung'])) { // Condition to check whether the page is my-account page

global $DB, $shopQuery;

    $subscriptionValue = $DB->$shopQuery('SELECT sub.nSubsId,sub.cBestellnummer,sub.cTerminationReason,nov.nStatuswert FROM xplugin_novalnetag_tsubscription_details sub JOIN tbestellung ord ON ord.cBestellNr = sub.cBestellnummer JOIN xplugin_novalnetag_tnovalnet_status nov ON ord.cBestellNr = nov.cNnorderid WHERE ord.kBestellung="' . $request['bestellung'] . '"', 1);

    if (is_object($subscriptionValue) && $subscriptionValue->nSubsId != '0' && $subscriptionValue->nStatuswert <=100 && $subscriptionValue->cTerminationReason == NULL) { // Condition to check whether order is a subscription order

        $placeholder = array('__NN_subscription_title','__NN_subscription_reasons','__NN_subscription_reason_error','__NN_select_type','__NN_subscription_reason_error','__NN_confirm_btn');

        $subsReasonPlaceholder = array('__NN_subscription_offer_expensive','__NN_subscription_fraud','__NN_subscription_partner_intervened','__NN_subscription_financial_problems','__NN_subscription_content_not_meeting_expectations','__NN_subscription_content_not_sufficient','__NN_subscription_interest_on_test_access','__NN_subscription_page_slow','__NN_subscription_satisfied','__NN_subscription_access_problems','__NN_subscription_others');

        $subscription = nnGetLanguageText($placeholder);

        $lowerShopSeries = strpos(getShopTemplate(), 'Mobil') !== false && $shopVersion == '3x';

        $smarty->assign(array(
            'subscription'      => $subscription,
            'subsValue'         => $subscriptionValue,
            'subsReason'        => nnGetLanguageText($subsReasonPlaceholder),
            'isMobileTemplate'  => $lowerShopSeries,
            'loadingimgUrl'     => $pluginPath . '/' . PFAD_PLUGIN_PAYMENTMETHOD . 'img/novalnet_loading.gif'
        ) );

        // Loads Novalnet subscription cancellation template
        $content = $smarty->fetch($oPlugin->cFrontendPfad . 'template/' . $shopVersion . '/subscription_cancellation.tpl');

        $globalInclude = PFAD_ROOT . PFAD_INCLUDES . 'globalinclude.php';

        $nnAppendScript = <<<HTML
        <input type='hidden' id='nn_subs_content' value='{$content}'>
        <input type='hidden' id='nn_mobile_version' value='{$lowerShopSeries}'>
        <input type='hidden' id='nn_order_no' value='{$subscriptionValue->cBestellnummer}'>
        <input type='hidden' id='nn_subs_error' value='{$subscription['subscription_reason_error']}'>
        <input type='hidden' id='nn_plugin_url' value='{$pluginPath}/adminmenu/inc/Novalnet.extension.php'>
        <input type='hidden' id='nn_global_include_url' value='{$globalInclude}'>
        <script type='text/javascript' src='{$pluginPath}/frontend/js/novalnet_subscriptions.js'></script>
HTML;
pq('head')->append($nnAppendScript); // PQ selector usage to append script
    }
} elseif (gibSeitenTyp()==PAGE_BESTELLVORGANG) { // Check if the page is a payment page only

    global $step;

    if ($step == 'Zahlung') {

        $nnLogos['master'] = 1;

        $nnCreditcardLogos = array('amex', 'maestro', 'cartasi');

        foreach($nnCreditcardLogos as $val) {
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


$nnScriptHead = <<<HTML
<input type='hidden' id='nn_logo_alt' value='{$paymentLogoAlt}'>
<input type='hidden' id='nn_payment' value='{$payment->cModulId}'>
<input type='hidden' id='nn_logos' value='{$logoQuery}'>
<input type='hidden' id='nn_shop_element' value='{$shopElement}'>
<input type='hidden' id='nn_payment_no' value='{$payment->kZahlungsart}'>
<input type='hidden' id='nn_mobile_version' value='{$lowerShopSeries}'>
<script type='text/javascript' src='{$pluginPath}/frontend/js/novalnet_logo.js'></script>
HTML;
                    pq('head')->append($nnScriptHead); // PQ selector usage to append script
                }
            }
        }
    }
}
