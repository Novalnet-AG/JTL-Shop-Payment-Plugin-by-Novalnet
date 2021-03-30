{**
 * Novalnet Direct Debit SEPA template
 * By Novalnet (https://www.novalnet.de)
 * Copyright (c) Novalnet
 *}

<div class="container form">
    <fieldset>
        <legend>{$smarty.session.Zahlungsart->angezeigterName[$smarty.session.cISOSprache]}</legend>

        <div id="sepa_javascript_enable" style="display:none;">
            <p class="box_error"><strong>{$nnLang.javascript_error}</strong></p>
        </div>

        {if $nnValidationError}
            <p class="box_error">{$nnValidationError}</p>
        {/if}

        <div id='nn_payment_sepa' style='display:block;'>
            <p class="box_info">
                {$nnLang.sepa_description}
                    {if $testMode}
                        {$nnLang.testmode}
                    {/if}
            </p>

        <input type="hidden" id="nn_payment" name="nn_payment" value="novalnet_sepa" />
        <input type="hidden" id="is_fraudcheck" name="is_fraudcheck" value="1">

        {if $one_click_shopping}
            <h5>
                <a id="nn_toggle_form" style="cursor:pointer"> {$nnLang.account_details_link_old} </a>
            </h5><br/>

            <input type="hidden" id="one_click_shopping" name="one_click_shopping" value="1">
            <input type="hidden" id="form_error" value="{$formError}">
            <input type="hidden" id="nn_account_display_text_saved" value="{$nnLang.account_details_link_old}">
            <input type="hidden" id="nn_account_display_text_new" value="{$nnLang.account_details_link_new}">

            <div id="nn_saved_details">
                <table style="width:100%">
                    <tr>
                        <td valign="top">{$nnLang.sepa_holder_name}</td>
                        <td valign="top">
                            <input type="text" value="{$nn_saved_details.referenceOption1}" readonly />
                        </td>
                    </tr>

                    <tr>
                        <td valign="top">{$nnLang.sepa_account_number}</td>
                        <td valign="top">
                            <input type="text" value="{$nn_saved_details.referenceOption2}" readonly />
                        </td>
                    </tr>

                    {if $nn_saved_details.referenceOption3}
                        <tr>
                            <td valign="top">{$nnLang.sepa_bank_code}</td>
                            <td valign="top">
                                <input type="text" value="{$nn_saved_details.referenceOption3}" readonly />
                            </td>
                        </tr>
                    {/if}
                </table>
            </div>
        {/if}

        <div id="nn_new_card_details">
            <div id='nn_loader' style='display:none'>
                {literal}
                    <style type='text/css'>
                            #nn_loader {
                                position  : fixed;
                                left      : 0px;
                                top       : 0px;
                                width     : 100%;
                                height    : 100%;
                                z-index   : 9999;
                                background: url('{/literal}{$paymentMethodPath}{literal}img/novalnet_loading.gif') 50% 50% no-repeat;
                            }
                    </style>
                {/literal}
            </div>

            {if $pin_enabled}
                <table style="width:50%">
                    <tr>
                        <td valign="top"> {$nnLang.callback_pin} </td>
                        <td valign="top">
                            <input type="text" name="nn_pin" id="nn_pin" autocomplete="off" />
                        </td>
                    </tr>
                        <input type="hidden" id="nn_pin_error_message" value="{$nnLang.callback_pin_error}">
                        <input type="hidden" id="nn_pin_empty_error_message" value="{$nnLang.callback_pin_error_empty}">
                    <tr>
                        <td></td>
                        <td valign="top">
                            <input type="checkbox" name="nn_forgot_pin" id="nn_forgot_pin" style="position:relative; left:0px; top:auto"> {$nnLang.callback_forgot_pin}
                        </td>
                    </tr>
                </table>
            {else}
                <table style="width:100%">
                    <tr>
                        <td valign="top"> {$nnLang.sepa_holder_name} <span style='color:red'>*</span></td>
                        <td valign="top"><input type="text" id="nn_sepaowner" autocomplete="off" value="{$smarty.session['Kunde']->cVorname} {$smarty.session['Kunde']->cNachname}" onkeypress="return isAlphanumeric(event)" /> </td>
                    </tr>

                    <tr>
                        <td valign="top"> {$nnLang.sepa_country_name} <span style='color:red'>*</span></td>
                        <td valign="top">
                        <select name="land" id="nn_sepa_country">
                            <option value="" selected disabled>{lang key="country" section="account data"}
                            </option>
                            {foreach name=land from=$countryList item=land}
                                <option value="{$land->cISO}" {if ($Einstellungen.kunden.kundenregistrierung_standardland==$land->cISO && empty($Kunde->cLand)) || !empty($Kunde->cLand) && $Kunde->cLand == $land->cISO}selected="selected"{/if}>{$land->cName}</option>
                            {/foreach}
                        </select>
                       </td>
                    </tr>

                    <tr>
                        <td valign="top"> {$nnLang.sepa_account_number} <span style='color:red'>*</span></td>
                        <td valign="top"><input type="text" id="nn_sepa_account_no" autocomplete="off" onkeypress="return isAlphanumeric(event)"/>
                        <span id="novalnet_sepa_iban_span"></span></td>
                    </tr>

                    <tr>
                        <td valign="top"> {$nnLang.sepa_bank_code} <span style='color:red'>*</span></td>
                        <td valign="top"><input type="text" id="nn_sepa_bank_code" autocomplete="off" onkeypress="return isAlphanumeric(event)"/>
                        <span id="novalnet_sepa_bic_span"></span></td>
                    </tr>

                    <tr>
                        <td></td>
                        <td valign="top"><input type="checkbox" id="nn_sepa_mandate_confirm" style="position:relative; left:0px; top:auto" /> {$nnLang.sepa_mandate_text} </td>
                    </tr>

                {if $pin_by_callback}
                    <tr>
                        <td valign="top"> {$nnLang.callback_phone_number} <span style='color:red'>*</span>
                        </td>
                        <td valign="top">
                            <input type="text" name="nn_tel_number" id="nn_tel_number" autocomplete="off" />
                            <input type="hidden" id="nn_tele_error_message" value="{$nnLang.callback_telephone_error}">
                        </td>
                    </tr>
                {elseif $pin_by_sms}
                    <tr>
                        <td valign="top"> {$nnLang.callback_sms} <span style='color:red'>*</span>
                        </td>
                        <td valign="top">
                            <input type="text" name="nn_mob_number" id="nn_mob_number" autocomplete="off" />
                            <input type="hidden" id="nn_mob_error_message" value="{$nnLang.callback_mobile_error}">
                        </td>
                    </tr>
                {/if}
                <input id="novalnet_vendor" type="hidden" value="{$vendorId}">
                <input id="novalnet_authcode" type="hidden" value="{$authCode}">
                <input id="nn_sepa_iban" type="hidden" value="">
                <input id="nn_sepa_bic" type="hidden" value="">
                <input id="nn_payment_hash" name="nn_payment_hash" type="hidden" value="" />
                <input id="nn_remote_ip" name="nn_remote_ip" type="hidden" value="{$remoteIp}" />
                <input id="nn_sepaunique_id" name="nn_sepaunique_id" type="hidden" value="{$uniqValue}" />
                <input id="nn_sepa_input_panhash" name="nn_sepa_input_panhash" type="hidden" value="{$panhash}" />
                <input id="nn_lang_mandate_confirm" type="hidden" value="{$nnLang.sepa_mandate_error}" />
                </table>
            {/if}
        </div>

        {if $isPaymentGuarantee}
            <table style="width:58%">
                <tr>
                    <td valign="top">
                        {$nnLang.guarantee_birthdate}<span style='color:red'>*</span>
                    </td>
                    <td valign="top">
                        <input type="text" value="" id="nn_dob" name="nn_dob" class="birthday form-control" placeholder="DD.MM.YYYY">
                        <input type="hidden" id="nn_dob_error_message" value="{$nnLang.birthdate_error}">
                        <input type="hidden" id="nn_dob_valid_message" value="{$nnLang.birthdate_valid_error}">
                        <input type="hidden" id="nn_guarantee_force"   value="{$guaranteeForce}">
                    </td>
                </tr>
            </table>
        {/if}

        <input id="nn_lang_valid_account_details" type="hidden" value="{$nnLang.sepa_error}" />
    </div>
    <script type="text/javascript" src="{$paymentMethodPath}js/novalnet_sepa.js"></script>
</fieldset>
</div>
