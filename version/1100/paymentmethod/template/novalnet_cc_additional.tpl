<fieldset>
    {if $nn_validation_error}
        <div class="alert alert-danger">{$nn_validation_error}</div>       
    {/if}
</fieldset>
<fieldset>
	<legend>{$payment_name}</legend>

	<div id="cc_javascript_enable" style="display:none;">
		<div class="alert alert-info"><strong>{$nn_lang.javascript_error}</strong></div>
	</div>
		
	<div id="nn_payment_cc" style="display:block;">
		<div class="alert alert-info">
			<span id="nn_creditcard_saved_desc">{$nn_lang.credit_card_desc}</span>
			<span id="nn_creditcard_redirect_desc">
				{$nn_lang.redirection_text}
				{$nn_lang.redirection_browser_text}
			</span>
				{if $test_mode}
					{$nn_lang.testmode}
				{/if}
		</div>
		
		<input type="hidden" id="nn_payment" name="nn_payment" value="novalnet_cc" />

		{if $one_click_shopping}
			<h4>
				<a id="nn_toggle_form" style="cursor:pointer"> {$nn_lang.card_details_link_old} </a>
			</h4>

			<input type="hidden" id="one_click_shopping" name="one_click_shopping" value="1">
			<input type="hidden" id="form_error" value="{$form_error}">
			<input type="hidden" id="nn_cc_display_text_saved" value="{$nn_lang.card_details_link_old}">
			<input type="hidden" id="nn_cc_display_text_new" value="{$nn_lang.card_details_link_new}">

			<div id="nn_saved_details">

				<div class="row">
					<div class="col-xs-12 col-md-6">
						<div class="form-group float-label-control">
						<label class="control-label">{$nn_lang.credit_card_type}</label>
						<input class="form-control" type="text" value="{$nn_saved_details.referenceOption1}" readonly />
						</div>
					</div>
				</div>
				
				<div class="row">
					<div class="col-xs-12 col-md-6">
						<div class="form-group float-label-control">
						<label class="control-label">{$nn_lang.credit_card_name}</label>
						<input class="form-control" type="text" value="{$nn_saved_details.referenceOption2}" readonly />
						</div>
					</div>
				</div>

				<div class="row">
					<div class="col-xs-12 col-md-6">
						<div class="form-group float-label-control">
						<label class="control-label">{$nn_lang.credit_card_number}</label>
						<input class="form-control" type="text" value="{$nn_saved_details.referenceOption3}" readonly />
						</div>
					</div>
				</div>

				<div class="row">
					<div class="col-xs-12 col-md-6">
						<div class="form-group float-label-control">
						<label class="control-label">{$nn_lang.credit_card_date}</label>
						<input class="form-control" type="text" value="{$nn_saved_details.referenceOption4}" readonly />
						</div>
					</div>
				</div>

				<div class="row">
					<div class="col-xs-12 col-md-6">
						<div class="form-group float-label-control required">
						<label class="control-label">{$nn_lang.credit_card_cvc}</label>
						<input class="form-control" type="text" name="nn_cvvnumber" id="nn_cvvnumber" onkeypress="return isNumberKey(event)" autocomplete="off">
					
						<span id="showcvc">
							<a onmouseover="showCvcInfo(true);" onmouseout="showCvcInfo(false);" style="text-decoration:none;">
								<img src="{$paymentMethodURL}img/novalnet_cvc_hint.png" style="padding-top:3%;" alt="CCV/CVC?">
							</a>
						</span>
						<span id="cvc_info" style="display:none;">
							<img src="{$paymentMethodURL}img/novalnet_creditcard_cvc.png">
						</span>
						
						</div>
					</div>
				</div>
			</div>	
		{/if}

		<div id="nn_new_card_details"></div>
		<input id="nn_cc_valid_error_ccmessage" type="hidden" value="{$nn_lang.credit_card_error}" />
	</div>
	<script type="text/javascript" src="{$paymentMethodURL}js/novalnet_cc.js" ></script>
</fieldset>
