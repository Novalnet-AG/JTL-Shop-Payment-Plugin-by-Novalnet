{**
 * Novalnet Cashpayment additional template
 * By Novalnet (https://www.novalnet.de)
 * Copyright (c) Novalnet
 *}

{if !empty($shopLatest)}
    <fieldset>
        <legend>{$smarty.session.Zahlungsart->angezeigterName[$smarty.session.cISOSprache]}</legend>
            <div class="alert alert-info">
                {$nnLanguage.cashpayment_description}
                    {if !empty($paymentTestmode)}
                        {$nnLanguage.testmode}
                    {/if}
            </div>
        <input id="nn_payment" name="nn_payment" type="hidden" value="novalnet_cashpayment" />
    </fieldset>
{else}
    <div class="container form">
        <fieldset>
            <legend>{$smarty.session.Zahlungsart->angezeigterName[$smarty.session.cISOSprache]}</legend>
            <p class="box_info">
                {$nnLanguage.cashpayment_description}
                {if !empty($paymentTestmode)}
                    {$nnLanguage.testmode}
                {/if}
            </p>
            <input id="nn_payment" name="nn_payment" type="hidden" value="novalnet_cashpayment" />
        </fieldset>
    </div>
{/if}
