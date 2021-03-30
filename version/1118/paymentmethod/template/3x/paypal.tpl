{**
 * Novalnet PayPal template
 * By Novalnet (https://www.novalnet.de)
 * Copyright (c) Novalnet
 *}

<div class="container form">
	<fieldset>
        <legend>{$smarty.session.Zahlungsart->angezeigterName[$smarty.session.cISOSprache]}</legend>

		<p class="box_info">
			<span id="nn_paypal_saved_desc">{$nnLang.paypal_desc}</span>
			<span id="nn_paypal_redirect_desc">
				{$nnLang.redirection_text}
				{$nnLang.redirection_browser_text}
			</span>
				{if $testMode}
					{$nnLang.testmode}
				{/if}
		</p>

		<input type="hidden" id="nn_payment" name="nn_payment" value="novalnet_paypal" />

		{if $one_click_shopping}
			<h5>
				<a id="nn_toggle_form" style="cursor:pointer"> {$nnLang.paypal_account_details_link_old} </a>
			</h5><br/>

			<input type="hidden" id="one_click_shopping" name="one_click_shopping" value="1">
			<input type="hidden" id="form_error" value="{$form_error}">
			<input type="hidden" id="nn_account_display_text_saved" value="{$nnLang.paypal_account_details_link_old}">
			<input type="hidden" id="nn_account_display_text_new" value="{$nnLang.paypal_account_details_link_new}">

			<div id="nn_saved_paypal_details">
				<table style="width:100%">
				{if $nn_saved_details.referenceOption1}
					<tr>
						<td valign="top">{$nnLang.paypal_tid_label}</td>
						<td valign="top">
							<input type="text" value="{$nn_saved_details.referenceOption1}" readonly />
						</td>
					</tr>
				{/if}
					<tr>
						<td valign="top">{$nnLang.tid_label}</td>
						<td valign="top">
							<input type="text" value="{$nn_saved_details.referenceOption2}" readonly />
						</td>
					</tr>
				</table>
			</div>
		{/if}

		<div id="nn_new_paypal_details"></div>

	<script type="text/javascript" src="{$paymentMethodPath}js/novalnet_paypal.js" ></script>
	</fieldset>
</div>
