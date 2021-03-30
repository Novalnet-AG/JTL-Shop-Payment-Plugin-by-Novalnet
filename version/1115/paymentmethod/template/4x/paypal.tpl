{**
 * Novalnet PayPal template
 * By Novalnet (https://www.novalnet.de)
 * Copyright (c) Novalnet
 *}

<fieldset>
    <legend>{$smarty.session['Zahlungsart']->angezeigterName[$smarty.session['cISOSprache']]}</legend>
        <div class="alert alert-info">
            <span id="nn_paypal_saved_desc">{$nnLang.paypal_desc}</span>
            <span id="nn_paypal_redirect_desc">
                {$nnLang.redirection_text}
                {$nnLang.redirection_browser_text}
            </span>
                {if !empty($testMode)}
                    {$nnLang.testmode}
                {/if}
        </div>

        <input type="hidden" id="nn_payment" name="nn_payment" value="novalnet_paypal" />

        {if !empty($one_click_shopping)}
            <h5>
                <a id="nn_toggle_form" style="cursor:pointer"> {$nnLang.paypal_account_details_link_old} </a>
            </h5>

            <input type="hidden" id="one_click_shopping" name="one_click_shopping" value="1">
            <input type="hidden" id="form_error" value="{$form_error}">
            <input type="hidden" id="nn_account_display_text_saved" value="{$nnLang.paypal_account_details_link_old}">
            <input type="hidden" id="nn_account_display_text_new" value="{$nnLang.paypal_account_details_link_new}">

            <div id="nn_saved_paypal_details">

			{if !empty($nn_saved_details.referenceOption1)}
                <div class="row">
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group float-label-control">
                        <label class="control-label">{$nnLang.paypal_tid_label}</label>
                        <input class="form-control" type="text" value="{$nn_saved_details.referenceOption1}" readonly />
                        </div>
                    </div>
                </div>
			{/if}
                <div class="row">
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group float-label-control">
                        <label class="control-label">{$nnLang.tid_label}</label>
                        <input class="form-control" type="text" value="{$nn_saved_details.referenceOption2}" readonly />
                        </div>
                    </div>
                </div>

            </div>
        {/if}

        <div id="nn_new_paypal_details"></div>

    <script type="text/javascript" src="{$paymentMethodPath}js/novalnet_paypal.js" ></script>
</fieldset>
