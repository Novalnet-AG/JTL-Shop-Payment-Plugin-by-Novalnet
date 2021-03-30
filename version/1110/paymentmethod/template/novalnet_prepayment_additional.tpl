{**
 * Novalnet Prepayment additional template
 * By Novalnet AG (https://www.novalnet.de)
 * Copyright (c) Novalnet AG
 *}

{if $shopLatest}
    <fieldset>
        <legend>{$smarty.session.Zahlungsart->angezeigterName[$smarty.session.cISOSprache]}</legend>
            <div class="alert alert-info">
                {$nnLanguage.invoice_description}
                    {if $paymentTestmode}
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
                {if $paymentTestmode}
                    {$nnLanguage.testmode}
                {/if}
            </p>
            <input id="nn_payment" name="nn_payment" type="hidden" value="novalnet_prepayment" />
        </fieldset>
    </div>
{/if}
