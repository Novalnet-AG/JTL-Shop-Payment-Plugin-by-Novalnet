{**
 * Novalnet Credit Card template
 * By Novalnet AG (https://www.novalnet.de)
 * Copyright (c) Novalnet AG
 *}

<fieldset>
    <legend>{$smarty.session['Zahlungsart']->angezeigterName[$smarty.session['cISOSprache']]}</legend>

    <div id="cc_javascript_enable" style="display:none;">
        <div class="alert alert-info"><strong>{$nn_lang.javascript_error}</strong></div>
    </div>

    <div id="nn_payment_cc" style="display:block;">
        <div class="alert alert-info">
            <span id="nn_creditcard_desc">{$nn_lang.credit_card_desc}</span>
                {if !empty($test_mode)}
                    {$nn_lang.testmode}
                {/if}
        </div>

        <input type="hidden" id="nn_payment" name="nn_payment" value="novalnet_cc" />
        <input type="hidden" id="is_cc3d_active" value="{$cc3dactive}" />
        <input type="hidden" id="nn_cc_saved_desc" value="{$nn_lang.credit_card_desc}" />
        <input type="hidden" id="nn_cc_redirect_desc" value="{$nn_lang.redirection_text} {$nn_lang.redirection_browser_text}" />

		{if !empty($one_click_shopping)}
			<h5>
				<a id="nn_toggle_form" style="cursor:pointer"> {$nn_lang.card_details_link_old} </a>
			</h5>

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

			</div>
		{/if}

		<div id="nn_new_card_details">
			<input id="nn_cc_hash" name="nn_cc_hash" type="hidden" />
			<input id="nn_cc_uniqueid" name="nn_cc_uniqueid" type="hidden" />
			<input id="nn_cc_formfields" type="hidden" value="{$creditcardFields|escape}" />
			<iframe id="novalnet_cc_iframe" name="novalnet_cc_iframe" width="100%" frameborder="0" scrolling="no" src="https://secure.novalnet.de/cc?signature={$form_identifier}&ln={$shopLanguage}" onload="loadElements();"></iframe>
		</div>

    </div>
    <script type="text/javascript" src="{$paymentMethodPath}js/novalnet_cc.js"></script>
</fieldset>
