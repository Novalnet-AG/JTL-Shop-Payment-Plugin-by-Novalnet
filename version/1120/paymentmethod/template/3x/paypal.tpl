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
 * Novalnet PayPal template
 *}

<fieldset>
    <legend>{$smarty.session['Zahlungsart']->angezeigterName[$smarty.session['cISOSprache']]}</legend>

		<p class="box_info">
			<span id="nn_paypal_saved_desc">{$nnLang.paypal_desc}</span>
			<span id="nn_paypal_redirect_desc">
				{$nnLang.redirection_text}
				{$nnLang.redirection_browser_text}
			</span>
                {if !empty($zeroBooking)}
                    {$nnLang.zero_booking_note}
                {/if}
				{if $testMode}
					{$nnLang.testmode}
				{/if}
		</p>

        <input type="hidden" id="nn_payment" name="nn_payment" value="novalnet_paypal" />

        {if !empty($one_click_shopping) && !empty($nn_saved_details)}
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

        <div id="nn_new_paypal_details">
			{if !empty($one_click_shopping) }
				<div class="row">
				<div class="col-xs-12 col-md-6">
					<div class="form-group float-label-control"></label>
						<label class="control-label" class="btn-block">
						<input type="checkbox" id="savepayment" value=""> {$nnLang.oneclick_paypal_save_data}
					</div>
				</div>
					<input id="nn_save_payment" name= "nn_save_payment" type="hidden" value="" />
				</div>
			{/if}
        </div>

    <script type="text/javascript" src="{$paymentMethodPath}js/novalnet_paypal.js" ></script>
</fieldset>

