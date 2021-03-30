{**
 * Novalnet Credit Card template
 * By Novalnet (https://www.novalnet.de)
 * Copyright (c) Novalnet
 *}

<div class="container form">
    <fieldset>
        <legend>{$smarty.session.Zahlungsart->angezeigterName[$smarty.session.cISOSprache]}</legend>

        <div id="cc_javascript_enable" style="display:none;">
            <p class="box_error"><strong>{$nnLang.javascript_error}</strong></p>
        </div>

        <div id="nn_payment_cc" style="display:block;">
            <p class="box_info">
                <span id="nn_creditcard_desc">{$nnLang.credit_card_desc}</span>
                {if $testMode}
                    {$nnLang.testmode}
                {/if}
            </p>

            <input type="hidden" id="nn_payment" name="nn_payment" value="novalnet_cc" />
            <input type="hidden" id="is_cc3d_active" value="{$cc3dactive}" />
            <input type="hidden" id="nn_cc_saved_desc" value="{$nnLang.credit_card_desc}" />
            <input type="hidden" id="nn_cc_redirect_desc" value="{$nnLang.redirection_text} {$nnLang.redirection_browser_text}" />

            <input type="hidden" id="nn_payment" name="nn_payment" value="novalnet_cc" />

            {if $one_click_shopping}
                <h5>
                    <a id="nn_toggle_form" style="cursor:pointer"> {$nnLang.card_details_link_old} </a>
                </h5><br/>

                <input type="hidden" id="one_click_shopping" name="one_click_shopping" value="1">
                <input type="hidden" id="form_error" value="{$formError}">
                <input type="hidden" id="nn_cc_display_text_saved" value="{$nnLang.card_details_link_old}">
                <input type="hidden" id="nn_cc_display_text_new" value="{$nnLang.card_details_link_new}">

                <div id="nn_saved_details">

                    <table style="width:100%">
                        <tr>
                            <td valign="top">{$nnLang.credit_card_type}</td>
                            <td valign="top">
                                <input type="text" value="{$nn_saved_details.referenceOption1}" readonly />
                            </td>
                        </tr>

                        <tr>
                            <td valign="top">{$nnLang.credit_card_name}</td>
                            <td valign="top">
                                <input type="text" value="{$nn_saved_details.referenceOption2}" readonly />
                            </td>
                        </tr>

                        <tr>
                            <td valign="top">{$nnLang.credit_card_number}</td>
                            <td valign="top">
                                <input type="text" value="{$nn_saved_details.referenceOption3}" readonly />
                            </td>
                        </tr>

                        <tr>
                            <td valign="top">{$nnLang.credit_card_date}</td>
                            <td valign="top">
                                <input type="text" value="{$nn_saved_details.referenceOption4}" readonly />
                            </td>
                        </tr>

                    </table>
                </div>
            {/if}

            <div id="nn_new_card_details">
                <input id="nn_cc_hash" name="nn_cc_hash" type="hidden" />
                <input id="nn_cc_uniqueid" name="nn_cc_uniqueid" type="hidden" />
                <input id="nn_cc_formfields" type="hidden" value="{$creditcardFields|escape}" />
                <iframe id="novalnet_cc_iframe" name="novalnet_cc_iframe" width="100%" frameborder="0" scrolling="no" src="https://secure.novalnet.de/cc?signature={$formIdentifier}&ln={$shopLanguage}" onload="loadElements();"></iframe>
            </div>
        </div>
        <script type="text/javascript" src="{$paymentMethodPath}js/novalnet_cc.js" ></script>
    </fieldset>
</div>
