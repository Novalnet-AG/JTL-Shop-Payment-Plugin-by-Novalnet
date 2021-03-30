{**
 * Novalnet Invoice template
 * By Novalnet AG (https://www.novalnet.de)
 * Copyright (c) Novalnet AG
 *}

<fieldset>
    {if $nnValidationError}
        <div class="alert alert-danger">{$nnValidationError}</div>
    {/if}
</fieldset>
<fieldset>
    <legend>{$smarty.session['Zahlungsart']->angezeigterName[$smarty.session['cISOSprache']]}</legend>

    <div class="alert alert-info">
        {$nn_lang.invoice_description}
            {if $test_mode}
                    {$nn_lang.testmode}
            {/if}
    </div>

    <input id="nn_payment" name="nn_payment" type="hidden" value="novalnet_invoice" />
    <input id="is_fraudcheck" name="is_fraudcheck" type="hidden" value="1">
    <script type="text/javascript" src="{$paymentMethodPath}js/novalnet_invoice.js" ></script>

    {if $pin_enabled}
        <div class="row">
            <div class="col-xs-12 col-md-6">
                <div class="form-group float-label-control">
                <label class="control-label">{$nn_lang.callback_pin}</label>
                <input class="form-control" type="text" name="nn_pin" id="nn_pin" autocomplete="off" />
                </div>
                <input type="hidden" id="nn_pin_error_message" value="{$nn_lang.callback_pin_error}">
                <input type="hidden" id="nn_pin_empty_error_message" value="{$nn_lang.callback_pin_error_empty}">
            </div>
        </div>

        <div class="row">
            <div class="col-xs-12 col-md-6">
                <span><input type="checkbox" name="nn_forgot_pin" id="nn_forgot_pin" /> {$nn_lang.callback_forgot_pin}</span>
            </div>
        </div>

    {else}
        {if $is_payment_guarantee}
            <div class="row">
                <div class="col-xs-12 col-md-6">
                    <div class="form-group float-label-control required">
                    <label class="control-label" for="nn_dob">{$nn_lang.guarantee_birthdate}</label>
                    <input type="text" value="" id="nn_dob" name="nn_dob" class="birthday form-control" placeholder="DD.MM.YYYY">
                    <input type="hidden" id="nn_dob_error_message" value="{$nn_lang.birthdate_error}">
                    <input type="hidden" id="nn_dob_valid_message" value="{$nn_lang.birthdate_valid_error}">
                    <input type="hidden" id="nn_guarantee_force"   value="{$guarantee_force}">
                    </div>
                </div>
            </div>
        {/if}

        {if $pin_by_callback}
            <div class="row">
                <div class="col-xs-12 col-md-6">
                    <div class="form-group float-label-control required">
                    <label class="control-label">{$nn_lang.callback_phone_number}</label>
                    <input class="form-control" type="text" name="nn_tel_number" id="nn_tel_number">
                    </div>
                    <input type="hidden" id="nn_tele_error_message" value="{$nn_lang.callback_telephone_error}">
                </div>
            </div>
        {elseif $pin_by_sms}
            <div class="row">
                <div class="col-xs-12 col-md-6">
                    <div class="form-group float-label-control required">
                        <label class="control-label">{$nn_lang.callback_sms}</label>
                        <input class="form-control" type="text" name="nn_mob_number" id="nn_mob_number">
                    </div>
                    <input type="hidden" id="nn_mob_error_message" value="{$nn_lang.callback_mobile_error}">
                </div>
            </div>
        {/if}
    {/if}
</fieldset>
