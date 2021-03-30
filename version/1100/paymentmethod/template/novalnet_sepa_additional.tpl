<fieldset>
    {if $nn_validation_error}
        <div class="alert alert-danger">{$nn_validation_error}</div>       
    {/if}
</fieldset>
<fieldset>
 	<legend>{$payment_name}</legend>
 	
	<div id="sepa_javascript_enable" style="display:none;">
		<div class="alert alert-info"><strong>{$nn_lang.javascript_error}</strong></div>
	</div>

	<div id='nn_payment_sepa' style='display:block;'>
		<div class="alert alert-info">
			{$nn_lang.sepa_description}
				{if $test_mode}
						{$nn_lang.testmode}
				{/if}
		</div>
		
		<input type="hidden" id="nn_payment" name="nn_payment" value="novalnet_sepa" />
		<input type="hidden" id="is_fraudcheck" name="is_fraudcheck" value="1">

		{if $one_click_shopping}
			<h4>
				<a id="nn_toggle_form" style="cursor:pointer"> {$nn_lang.account_details_link_old} </a>
			</h4>

			<input type="hidden" id="one_click_shopping" name="one_click_shopping" value="1">
			<input type="hidden" id="form_error" value="{$form_error}">
			<input type="hidden" id="nn_account_display_text_saved" value="{$nn_lang.account_details_link_old}">
			<input type="hidden" id="nn_account_display_text_new" value="{$nn_lang.account_details_link_new}">

			<div id="nn_saved_details">

				<div class="row">
					<div class="col-xs-12 col-md-6">
						<div class="form-group float-label-control">
						<label class="control-label">{$nn_lang.sepa_holder_name}</label>
						<input class="form-control" type="text" value="{$nn_saved_details.referenceOption1}" readonly />
						</div>
					</div>
				</div>
				
				<div class="row">
					<div class="col-xs-12 col-md-6">
						<div class="form-group float-label-control">
						<label class="control-label">{$nn_lang.sepa_account_number}</label>
						<input class="form-control" type="text" value="{$nn_saved_details.referenceOption2}" readonly />
						</div>
					</div>
				</div>

				<div class="row">
					<div class="col-xs-12 col-md-6">
						<div class="form-group float-label-control">
						<label class="control-label">{$nn_lang.sepa_bank_code}</label>
						<input class="form-control" type="text" value="{$nn_saved_details.referenceOption3}" readonly />
						</div>
					</div>
				</div>

				<div class="row">
					<div class="col-xs-18 col-md-12">
						<div class="form-group float-label-control required">
						<input type="checkbox" id="nn_sepa_saved_details_confirm" /> 
						{$nn_lang.sepa_mandate_text}
						<span style="color:red">*</span>
						</div>
					</div>
				</div>
			</div>	
		{/if}

		<div id="nn_new_card_details">
			{if !$is_iframe}
				<div id="nn_loader" style="display:none"></div>

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
					<div class="row">
						<div class="col-xs-12 col-md-6">
							<div class="form-group float-label-control required">
							<label class="control-label">{$nn_lang.sepa_holder_name}</label>
							<input class="form-control" type="text" name="nn_sepaowner" id="nn_sepaowner" value="{$sepa_holder}" onkeypress="return isAlphanumeric(event)" />
							</div>
						</div>
					</div>

					<div class="row">
						<div class="col-xs-12 col-md-6">
							<div class="form-group float-label-control required"">
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
							<div class="form-group float-label-control required">
							<label class="control-label">{$nn_lang.sepa_account_number}</label>
							<input class="form-control" type="text" name="nn_sepa_account_no" id="nn_sepa_account_no" onkeypress="return isAlphanumeric(event)" autocomplete="off" /><span id="novalnet_sepa_iban_span"></span>
							</div>
						</div>
					</div>
					
					<div class="row">
						<div class="col-xs-12 col-md-6">
							<div class="form-group float-label-control required">
							<label class="control-label">{$nn_lang.sepa_bank_code}</label>
							<input class="form-control" type="text" name="nn_sepa_bank_code" id="nn_sepa_bank_code" size="32" onkeypress="return isAlphanumeric(event)" autocomplete="off" /><span id="novalnet_sepa_bic_span"></span>
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

					{elseif $reply_by_email}
						<div class="row">
							<div class="col-xs-12 col-md-6">
								<div class="form-group float-label-control required">
								<label class="control-label">{$nn_lang.callback_mail}</label>
								<input class="form-control" type="text" name="nn_mail" id="nn_mail" value="{$lang_sepa_customer_email}">
								<input type="hidden" id="nn_mail_error_message" value="{$nn_lang.callback_email_pin}">
								</div>
							</div>
						</div>
					{/if}
				<input id="novalnet_vendor" type="hidden" value="{$vendor_id}">
				<input id="novalnet_authcode" type="hidden" value="{$auth_code}">
				<input id="nn_sepa_iban" type="hidden" value="">
				<input id="nn_sepa_bic" type="hidden" value="">
				<input id="nn_payment_hash" name="nn_payment_hash" type="hidden" value="" />
				<input id="nn_sepaunique_id" name="nn_sepaunique_id" type="hidden" value="{$uniq_value}" />
				<input id="nn_sepapanhash" name="nn_sepapanhash" type="hidden" value="">        
				<input id="nn_sepa_input_panhash" name="nn_sepa_input_panhash" type="hidden" value="{$panhash}" />
				<input id="nn_lang_mandate_confirm" type="hidden" value="{$nn_lang.sepa_mandate_error}" />
				{/if}				
			{/if}
		</div>

		{if $is_payment_guarantee}
			<div class="row">
				<div class="col-xs-12 col-md-6">
					<div class="form-group float-label-control required">
					<label class="control-label" for="birthday">{$nn_lang.sepa_birthdate}</label>
					<input type="text" value="{$smarty.now|date_format:'%d.%m.%Y'}" id="nn_dob" name="nn_dob" class="birthday form-control" placeholder="DD.MM.YYYY">
					<input type="hidden" id="nn_dob_error_message" value="{$nn_lang.birthdate_error}">
					</div>
				</div>
			</div>
		{/if}
		
		<input id="nn_lang_valid_account_details" type="hidden" value="{$nn_lang.sepa_error}" />
	</div>
	<script type="text/javascript" src="{$paymentMethodURL}js/novalnet_sepa.js" ></script>	
</fieldset>
