{**
 * Novalnet redirection payments additional template
 * By Novalnet (https://www.novalnet.de)
 * Copyright (c) Novalnet
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
