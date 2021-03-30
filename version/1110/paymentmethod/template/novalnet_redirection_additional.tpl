{**
 * Novalnet redirection payments additional template
 * By Novalnet AG (https://www.novalnet.de)
 * Copyright (c) Novalnet AG
 *}

{if $shopLatest}
    <fieldset>
        <legend>{$smarty.session.Zahlungsart->angezeigterName[$smarty.session.cISOSprache]}</legend>
            <div class="alert alert-info">
                {$nn_lang.redirection_text}
                {$nn_lang.redirection_browser_text}
                    {if $test_mode}
                        {$nn_lang.testmode}
                    {/if}
            </div>
        <input id="nn_payment" name="nn_payment" type="hidden" value="{$payment_name}" />
    </fieldset>
{else}
    <div class="container form">
        <fieldset>
            <legend>{$smarty.session.Zahlungsart->angezeigterName[$smarty.session.cISOSprache]}</legend>

            <p class="box_info">
                {$nn_lang.redirection_text}
                {$nn_lang.redirection_browser_text}
                {if $test_mode}
                    {$nn_lang.testmode}
                {/if}
            </p>
            <input id="nn_payment" name="nn_payment" type="hidden" value="{$payment_name}" />
        </fieldset>
    </div>
{/if}
