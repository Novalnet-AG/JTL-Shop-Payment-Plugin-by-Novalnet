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
 * Novalnet Credit Card template
 *}

<fieldset>
    <legend>{$smarty.session['Zahlungsart']->angezeigterName[$smarty.session['cISOSprache']]}</legend>

    <div id="cc_javascript_enable" style="display:none;">
        <div class="alert alert-info"><strong>{$nnLang.javascript_error}</strong></div>
    </div>

    <div id="nn_payment_cc" style="display:block;">
            <p class="box_info">
                <span id="nn_creditcard_desc">{$nnLang.credit_card_desc}</span>
                {if !empty($zeroBooking)}
                    {$nnLang.zero_booking_note}
                {/if}
                {if $testMode}
                    {$nnLang.testmode}
                {/if}
            </p>

        <input type="hidden" id="nn_payment" name="nn_payment" value="novalnet_cc" />
        <input type="hidden" id="is_cc3d_active" value="{$cc3dactive}" />
        <input type="hidden" id="nn_cc_saved_desc" value="{$nnLang.credit_card_desc}" />
        <input type="hidden" id="nn_cc_redirect_desc" value="{$nnLang.redirection_text} {$nnLang.redirection_browser_text}" />

        {if !empty($one_click_shopping) && !empty($nn_saved_details)}
            <h5>
                <a id="nn_toggle_form" style="cursor:pointer"> {$nnLang.card_details_link_old} </a>
            </h5>

            <input type="hidden" id="one_click_shopping" name="one_click_shopping" value="1">
            <input type="hidden" id="form_error" value="{$formError}">
            <input type="hidden" id="nn_cc_display_text_saved" value="{$nnLang.card_details_link_old}">
            <input type="hidden" id="nn_cc_display_text_new" value="{$nnLang.card_details_link_new}">
            
            <div id="nn_saved_details">

                <div class="row">
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group float-label-control">
                        <label class="control-label">{$nnLang.credit_card_type}</label>
                        <input class="form-control" type="text" value="{$nn_saved_details.referenceOption1}" readonly />
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group float-label-control">
                        <label class="control-label">{$nnLang.credit_card_name}</label>
                        <input class="form-control" type="text" value="{$nn_saved_details.referenceOption2}" readonly />
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group float-label-control">
                        <label class="control-label">{$nnLang.credit_card_number}</label>
                        <input class="form-control" type="text" value="{$nn_saved_details.referenceOption3}" readonly />
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group float-label-control">
                        <label class="control-label">{$nnLang.credit_card_date}</label>
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
            <iframe id="novalnet_cc_iframe" name="novalnet_cc_iframe" width="100%" frameborder="0" scrolling="no" src="https://secure.novalnet.de/cc?api={$formIdentifier}&ln={$shopLanguage}" onload="loadElements();"></iframe>
        
			{if !empty($one_click_shopping)}
			<div class="row">
				<div class="col-xs-12">
					<div class="form-group float-label-control"></label>
						<label class="control-label" class="btn-block">
						<input type="checkbox" id="savepayment" value=""> {$nnLang.oneclick_cc_save_data}
					</div>
				</div>
					<input id="nn_save_payment" name= "nn_save_payment" type="hidden" value="" />
			</div>
			{/if}
        </div>
        
    </div>
    <script type="text/javascript" src="{$paymentMethodPath}js/novalnet_cc.js"></script>
</fieldset>
