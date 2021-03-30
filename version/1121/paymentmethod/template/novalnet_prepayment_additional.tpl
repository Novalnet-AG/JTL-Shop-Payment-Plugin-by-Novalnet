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
 * Novalnet Prepayment additional template
 *}

{if !empty($shopLatest)}
    <fieldset>
        <legend>{$smarty.session.Zahlungsart->angezeigterName[$smarty.session.cISOSprache]}</legend>
            <div class="alert alert-info">
                {$nnLanguage.invoice_description}
                    {if !empty($paymentTestmode)}
                        {$nnLanguage.testmode}
                    {/if}
            </div>
        <input id="nn_payment" name="nn_payment" type="hidden" value="novalnet_prepayment" />
    </fieldset>
{else}
    <div class="container form">
        <fieldset>
            <legend>{$smarty.session.Zahlungsart->angezeigterName[$smarty.session.cISOSprache]}</legend>
            <p class="box_info">
                {$nnLanguage.invoice_description}
                {if !empty($paymentTestmode)}
                    {$nnLanguage.testmode}
                {/if}
            </p>
            <input id="nn_payment" name="nn_payment" type="hidden" value="novalnet_prepayment" />
        </fieldset>
    </div>
{/if}
