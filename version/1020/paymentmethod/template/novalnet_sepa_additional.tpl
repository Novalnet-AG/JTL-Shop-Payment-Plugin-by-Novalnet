<fieldset>
    {if $error}
        <div class="alert alert-danger">{$error_desc}</div>
    {/if}
</fieldset>
<fieldset>
  <legend>{$payment_name}</legend>

    <div id="nn_loader" style="display:none"></div>

    <div id="sepa_javascript_enable" style="display:block;">
       <strong>{$nn_lang.javascript_error}</strong>
    </div>

    <div id="nn_payment_sepa" style="display:none;">
        <div class="alert alert-info">
            {$nn_lang.sepa_description}
                {if $test_mode}
                        {$nn_lang.testmode}
                {/if}
        </div>
        <input id="nn_payment" name="payment" type="hidden" value="novalnet_sepa" />
        {if $pin_error}
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
                <div>
                    <span>
                        <input type="checkbox" name="nn_forgot_pin" id="nn_forgot_pin" />   {$nn_lang.callback_forgot_pin}
                    </span>
                </div>
            </div>
        </div>
        {else}
        <div class="row">
           <div class="col-xs-12 col-md-6">
                <div class="form-group float-label-control sepa_name required">
                    <label class="control-label">{$nn_lang.sepa_holder_name}</label>
                    <input class="form-control" type="text" name="nn_sepaowner" id="nn_sepaowner" size="32" value="{$sepa_holder}" onkeypress="return isAlphanumeric(event)" />
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-xs-12 col-md-6">
                <div class="form-group float-label-control required">
                    <label class="control-label">{$nn_lang.sepa_country_name}</label>
                        <select name="land" id="nn_sepa_country" class="country_input form-control">
                            <option value="" selected disabled>{lang key="country" section="account data"}
                            </option>
                            {foreach name=land from=$country_list item=land}
                                <option value="{$land->cISO}" {if ($Einstellungen.kunden.kundenregistrierung_standardland==$land->cISO && empty($Kunde->cLand)) || !empty($Kunde->cLand) && $Kunde->cLand == $land->cISO}selected="selected"{/if}>{$land->cName}</option>
                            {/foreach}
                        </select>
                </div>
            </div>
        </div>
        <div class="row">
           <div class="col-xs-12 col-md-6">
                <div class="form-group float-label-control sepa_acc_no required">
                    <label class="control-label">{$nn_lang.sepa_account_number}</label>
                    <input class="form-control" type="text" id="nn_sepa_account_no" size="32" onkeypress="return isAlphanumeric(event)" autocomplete="off"/><span id="novalnet_sepa_iban_span"></span>
                </div>
            </div>
        </div>
        <div class="row">
           <div class="col-xs-12 col-md-6">
                <div class="form-group float-label-control sepa_bank_code required">
                    <label class="control-label">{$nn_lang.sepa_bank_code}</label>
                    <input class="form-control" type="text" id="nn_sepa_bank_code" size="32" onkeypress="return isAlphanumeric(event)" autocomplete="off"/><span id="novalnet_sepa_bic_span"></span>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-xs-18 col-md-12">
                <div class="form-group float-label-control required">
                    <input type="checkbox" id="nn_sepa_mandate_confirm" />
                    {$nn_lang.sepa_mandate_text}
                    <span style="color:red">*</span>
                </div>
            </div>
        </div>

         {if $pin_by_callback}
          <div class="row">
            <div class="col-xs-12 col-md-6">
                <div class="form-group float-label-control sepa_tel required">
                    <label class="control-label">{$nn_lang.callback_phone_number}</label>
                    <input class="form-control" type="text" name="nn_telnumber" id="nn_telnumber" size="32">
                </div>
                <input type="hidden" id="nn_tele_error_message" value="{$nn_lang.callback_telephone_error}">
            </div>
           </div>
          {elseif $pin_by_sms}
            <div class="row">
             <div class="col-xs-12 col-md-6">
                <div class="form-group float-label-control sepa_sms required">
                    <label class="control-label">{$nn_lang.callback_sms}</label>
                    <input class="form-control" type="text" name="nn_mob_number" id="nn_mob_number" size="32">
                </div>
                 <input type="hidden" id="nn_mob_error_message" value="{$nn_lang.callback_mobile_error}">
            </div>
           </div>
          {/if}
          <input type="hidden" id="nn_vendor" value="{$vendor_id}" />
          <input type="hidden" id="nn_authcode" value="{$auth_code}" />
          <input id="nn_sepaunique_id" name="nn_sepaunique_id" type="hidden" value="{$uniq_sepa_value}" />
          <input id="nn_sepapanhash" name="nn_sepapanhash" type="hidden" value="">
          <input id="nn_sepa_iban" type="hidden" value="">
          <input id="nn_sepa_bic" type="hidden" value="">
          <input id="nn_sepa_input_panhash" name="nn_sepa_input_panhash" type="hidden" value="{$panhash}" />
          <input id="nn_lang_valid_account_details" type="hidden" value="{$nn_lang.sepa_error}" />
          <input id="nn_lang_valid_merchant_credentials" type="hidden" value="{$nn_lang.merchant_error}" />
          <input id="nn_lang_mandate_confirm" type="hidden" value="{$nn_lang.sepa_mandate_error}" />
          <input id="nn_lang_valid_country_details" type="hidden" value="{$nn_lang.sepa_country_error}" />
        {/if}
        </div>
    <script type="text/javascript" src="{$filePath}/js/novalnet_sepa.js"></script>
</fieldset>
