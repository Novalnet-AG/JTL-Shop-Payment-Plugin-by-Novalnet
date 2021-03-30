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
 * Novalnet Direct Debit SEPA template
 *}

<fieldset>
    {if !empty($nnValidationError)}
        <div class="alert alert-danger">{$nnValidationError}</div>
    {/if}
</fieldset>
<fieldset>
    <legend>{$smarty.session['Zahlungsart']->angezeigterName[$smarty.session['cISOSprache']]}</legend>

    <div id="sepa_javascript_enable" style="display:none;">
        <div class="alert alert-info"><strong>{$nnLang.javascript_error}</strong></div>
    </div>

    <div id='nn_payment_sepa' style='display:block;'>
            <p class="box_info">
                {$nnLang.sepa_description}
                    {if !empty($zeroBooking)}
                        {$nnLang.zero_booking_note}
                    {/if}
                    {if $testMode}
                        {$nnLang.testmode}
                    {/if}
            </p>

        <input type="hidden" id="nn_payment" name="nn_payment" value="novalnet_sepa" />
        <input type="hidden" id="is_fraudcheck" name="is_fraudcheck" value="1">

        {if !empty($one_click_shopping) && !empty($nn_saved_details)}
            <h5>
                <a id="nn_toggle_form" style="cursor:pointer"> {$nnLang.account_details_link_old} </a>
            </h5>

            <input type="hidden" id="one_click_shopping" name="one_click_shopping" value="1">
            <input type="hidden" id="form_error" value="{$formError}">
            <input type="hidden" id="nn_account_display_text_saved" value="{$nnLang.account_details_link_old}">
            <input type="hidden" id="nn_account_display_text_new" value="{$nnLang.account_details_link_new}">

            <div id="nn_saved_details">
            
                <div class="row">
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group float-label-control">
                        <label class="control-label">{$nnLang.sepa_holder_name}</label>
                        <input class="form-control" type="text" value="{$nn_saved_details.referenceOption1}" readonly />
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group float-label-control">
                        <label class="control-label">{$nnLang.sepa_account_number}</label>
                        <input class="form-control" type="text" value="{$nn_saved_details.referenceOption2}" readonly />
                        </div>
                    </div>
                </div>

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
            
            
       
            {if !empty($pin_enabled)}
                <div class="row">
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group float-label-control">
                        <label class="control-label">{$nnLang.callback_pin}</label>
                        <input class="form-control" type="text" name="nn_pin" id="nn_pin" autocomplete="off" />
                        </div>
                        <input type="hidden" id="nn_pin_error_message" value="{$nnLang.callback_pin_error}">
                        <input type="hidden" id="nn_pin_empty_error_message" value="{$nnLang.callback_pin_error_empty}">
                    </div>
                </div>

                <div class="row">
                    <div class="col-xs-12 col-md-6">
                        <span><input type="checkbox" name="nn_forgot_pin" id="nn_forgot_pin" /> {$nnLang.callback_forgot_pin}</span>
                    </div>
                </div>
            {else}
                <div class="row">
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group float-label-control required">
                        <label class="control-label" for="nn_sepaowner">{$nnLang.sepa_holder_name}</label>
                        <input class="form-control" type="text" name="nn_sepaowner" id="nn_sepaowner" value="{$smarty.session['Kunde']->cVorname} {$smarty.session['Kunde']->cNachname}" onkeypress="return isAlphanumeric(event)" />
                        </div>
                    </div>
                </div>

               

                <div class="row">
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group float-label-control required">
                        <label class="control-label" for="nn_sepa_account_number">{$nnLang.sepa_account_number}</label>
                        <input class="form-control" type="text" id="nn_sepa_account_no" name="nn_sepa_account_no" size="32" onkeypress="return isAlphanumeric(event)" autocomplete="off" />
                        </div>
                        <div>
                        <a href="#iban_details" data-toggle="collapse">{$nnLang.sepa_mandate_text}</a><br>
                        <div id="iban_details" class="collapse card-body" style="background:whitesmoke;padding:3%;">
                            <div>{$nnLang.sepa_mandate_instruction_one}</div><br>
                            <div><b>{$nnLang.sepa_mandate_instruction_two}</div></b><br>
                            <div>{$nnLang.sepa_mandate_instruction_three}</div>
                        </div>
                        </div>
                    </div>
                </div>
                
                {if !empty($one_click_shopping) }
                    <div class="row">
                        <div class="col-xs-12 col-md-6">
                            <div class="form-group float-label-control"></label>
                                <label class="control-label" class="btn-block">
                                    <input type="checkbox" id="savepayment" value=""> {$nnLang.oneclick_sepa_save_data}
                            </div>
                        </div>
                            <input id="nn_save_payment" name= "nn_save_payment" type="hidden" value="" />
                    </div>
                {/if}
                
                {if !empty($pin_by_callback)}
                    <div class="row">
                        <div class="col-xs-12 col-md-6">
                            <div class="form-group float-label-control required">
                            <label class="control-label" for="nn_tel_number">{$nnLang.callback_phone_number}</label>
                            <input class="form-control" type="text" name="nn_tel_number" id="nn_tel_number">
                            </div>
                            <input type="hidden" id="nn_tele_error_message" value="{$nnLang.callback_telephone_error}">
                        </div>
                    </div>

                {elseif !empty($pin_by_sms)}
                    <div class="row">
                        <div class="col-xs-12 col-md-6">
                            <div class="form-group float-label-control required">
                            <label class="control-label" for="nn_mob_number">{$nnLang.callback_sms}</label>
                            <input class="form-control" type="text" name="nn_mob_number" id="nn_mob_number">
                            </div>
                            <input type="hidden" id="nn_mob_error_message" value="{$nnLang.callback_mobile_error}">
                        </div>
                    </div>
                {/if}
                
                <input id="nn_sepa_iban" type="hidden" value="" name="nn_sepa_iban">
                
            {/if}
        </div>

        {if !empty($isPaymentGuarantee) && empty($company)}
            <div class="row">
                <div class="col-xs-12 col-md-6">
                    <div class="form-group float-label-control required">
                    <label class="control-label" for="nn_dob">{$nnLang.guarantee_birthdate}</label>
                    <input type="text" value="" id="nn_dob" name="nn_dob" class="birthday form-control" placeholder="DD.MM.YYYY">
                    <input type="hidden" id="nn_dob_error_message" value="{$nnLang.birthdate_error}">
                    <input type="hidden" id="nn_dob_valid_message" value="{$nnLang.birthdate_valid_error}">
                    <input type="hidden" id="nn_guarantee_force"   value="{$guaranteeForce}">
                    </div>
                </div>
            </div>
        {/if}

        <input id="nn_lang_valid_account_details" type="hidden" value="{$nnLang.sepa_error}" />
    </div>

    <script type="text/javascript" src="{$paymentMethodPath}js/novalnet_sepa.js"></script>
</fieldset>
