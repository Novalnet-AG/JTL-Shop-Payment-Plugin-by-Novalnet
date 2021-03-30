{**
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
 * Novalnet redirection payments additional template
 *}

{if !empty($shopLatest)}
    <fieldset>
        <legend>{$smarty.session.Zahlungsart->angezeigterName[$smarty.session.cISOSprache]}</legend>
            <div class="alert alert-info">
                {$nnLang.redirection_text}
                {$nnLang.redirection_browser_text}
                    {if !empty($testMode)}
                        {$nnLang.testmode}
                    {/if}
            </div>
        <input id="nn_payment" name="nn_payment" type="hidden" value="{$paymentName}" />
    </fieldset>
{else}
    <div class="container form">
        <fieldset>
            <legend>{$smarty.session.Zahlungsart->angezeigterName[$smarty.session.cISOSprache]}</legend>

            <p class="box_info">
                {$nnLang.redirection_text}
                {$nnLang.redirection_browser_text}
                {if !empty($testMode)}
                    {$nnLang.testmode}
                {/if}
            </p>
            <input id="nn_payment" name="nn_payment" type="hidden" value="{$paymentName}" />
        </fieldset>
    </div>
{/if}
