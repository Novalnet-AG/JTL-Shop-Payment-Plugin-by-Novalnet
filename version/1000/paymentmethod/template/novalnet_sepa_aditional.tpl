<link rel='stylesheet' type='text/css' href="{$filePath}/css/novalnet_sepa.css">
<div class="container form">
<div id="nn_loader" style="display:none"></div>
<style type="text/css">
	{literal}
	#nn_loader
	{
		position: fixed;
		left	: 0px;
		top		: 0px;
		width	: 100%;
		height	: 100%;
		z-index	: 9999;
		background: url('{/literal}{$filePath}{literal}/img/loading.gif') 50% 50% no-repeat;
	}
	{/literal}
</style>
<fieldset>
    <legend>{$payment_name}</legend>
	<div id='sepa_javascript_enable' style=display:block;>
	   <strong>{$nn_lang.javascript_error}</strong>
	</div>
	<div id='nn_payment_sepa' style=display:none;>
    	<input id="nn_payment" name="payment" type="hidden" value="novalnet_sepa" />
    {if $pin_error}
	<table style="width:50%">
	    <tr>
	 	<td valign="top"> {$nn_lang.callback_pin} </td>
		<td valign="top"><input type="text" name="nn_pin" id="nn_pin" autocomplete="off" /> </td>
	   </tr>
		<input type="hidden" id="nn_pin_error_message" value="{$nn_lang.callback_pin_error}">
		<input type="hidden" id="nn_pin_empty_error_message" value="{$nn_lang.callback_pin_error_empty}">
	   <tr>
	        <td></td>
		<td valign="top"><input type="checkbox" name="nn_forgot_pin" id="nn_forgot_pin" /> {$nn_lang.callback_forgot_pin} </td>
	   </tr>
	</table>
    {else}
	{if $error}
	    {if $error_desc}
		<p class="box_error">{$error_desc}</p>
	    {/if}
    {/if}
    {$nn_lang.sepa_description}
    {$lang_sepa_test_mode_info}
    <br><br>

    <table style="width:100%">
    	<tr>
            <td valign="top"> {$nn_lang.sepa_holder_name}<span style='color:red'>*</span></td>
	    <td valign="top"><input type="text" name="nn_sepaowner" id="nn_sepaowner" autocomplete="off" value="{$sepa_holder}" onkeypress="return isAlphanumeric(event)" /> </td>
        </tr>
        <tr>
           <td valign="top">{$nn_lang.sepa_country_name}<span style='color:red'>*</span></td>
	   <td valign="top">
		<select id="nn_sepa_country">
		<option value = DE selected> {if $lang_code == 'EN'} Germany {else} Deutschland {/if} </option>
			{foreach name = land from = $country_list item=land}
				<option value="{$land->cISO}">{$land->cName}</option>
			{/foreach}
		</select>
	   </td>
        </tr>
        <tr>
           <td valign="top"> {$nn_lang.sepa_account_number}<span style='color:red'>*</span></td>
           <td valign="top"><input type="text" id= "nn_sepa_account_no" autocomplete="off" onkeypress="return isAlphanumeric(event)"/> <span id="novalnet_sepa_iban_span"></span></td>
        </tr>
        <tr>
           <td valign="top"> {$nn_lang.sepa_bank_code}<span style='color:red'>*</span></td>
           <td valign="top"><input type="text" id= "nn_sepa_bank_code" autocomplete="off" onkeypress="return isAlphanumeric(event)"/> <span id="novalnet_sepa_bic_span"></span></td>
        </tr>
        <tr>
	   <td></td>
           <td valign="top"><input type="checkbox" id="nn_sepa_mandate_confirm" style="margin-right:1%;position:static !important"/></a>{$nn_lang.sepa_mandate_text} </td>
        </tr>

      {if $pin_by_callback}
      <tr>
         <td valign="top"> {$nn_lang.callback_phone_number}<span style='color:red'>*</span></td>
         <td valign="top"><input type="text" name="nn_telnumber" id="nn_telnumber" autocomplete="off"  /> </td>
      </tr>
         <input type="hidden" id="nn_tele_error_message" value="{$nn_lang.callback_telephone_error}">
      {elseif $pin_by_sms}
      <tr>
         <td valign="top"> {$nn_lang.callback_sms}<span style='color:red'>*</span></td>
         <td valign="top"><input type="text" name="nn_mob_number" id="nn_mob_number" autocomplete="off" /> </td>
      </tr>
         <input type="hidden" id="nn_mob_error_message" value="{$nn_lang.callback_mobile_error}">
      {elseif $reply_by_email}
      <tr>
         <td valign="top"> {$nn_lang.callback_mail}<span style='color:red'>*</span></td>
         <td valign="top"><input type="text" name="nn_mail" id="nn_mail" autocomplete="off" value="{$sepa_holder_mail}"/> </td>
      </tr>
     	 <input type="hidden" id="nn_mail_error_message" value="{$nn_lang.callback_email_pin}">
      {/if}
        <input type="hidden" id="nn_vendor" value="{$vendor_id}" />
        <input type="hidden" id="nn_authcode" value="{$auth_code}" />
        <input id="nn_sepaunique_id" name="nn_sepaunique_id" type="hidden" value="{$uniq_sepa_value}" />
        <input id="nn_sepapanhash" name="nn_sepapanhash" type="hidden" value="">
        <input id="nn_sepa_iban" type="hidden" value="">
        <input id="nn_sepa_bic" type="hidden" value="">
        <input id="sepa_mandate_ref" type="hidden" value="">
        <input id="sepa_mandate_date" type="hidden" value="">
        <input id="sepa_company" name="sepa_company" type="hidden" value="{$sepa_holder_company}">
        <input id="nn_sepa_input_panhash" name="nn_sepa_input_panhash" type="hidden" value="{$panhash}" />
        <input id="nn_lang_valid_account_details" type="hidden" value="{$nn_lang.sepa_error}" />
        <input id="nn_lang_valid_merchant_credentials" type="hidden" value="{$nn_lang.merchant_error}" />
        <input id="nn_lang_mandate_confirm" type="hidden" value="{$nn_lang.sepa_mandate_error}" />
    </table>
    {/if}
    </div>
	<script type = "text/javascript"  src="{$filePath}/js/novalnet_sepa.js" ></script>
  </fieldset>
</div>

<!-- SEPA MANDATE-->
<div class="bgCover">&nbsp;</div>
<div id='sepa_mandate_overlay_block_first' style='display:none;' class='overlay_window_block'>
<img src='{$filePath}/img/loading.gif' alt='Loading...'/>
</div>

<div id='sepa_mandate_overlay_block' style='display:none;' class='overlay_window_block'>
	<div class='nn_header'>
		<h1>{$nn_lang.sepa_mandate_title}</h1>
	</div>
	<div class='body_div' id='overlay_window_block_body'>
		<table>
			<tr>
				<td>{$nn_lang.sepa_payee}</td><td>:</td><td><span id='sepa_overlay_payee_span'>&nbsp;</span></td>
			</tr>
			<tr>
				<td>{$nn_lang.sepa_payee_number}</td><td>:</td><td><span id='sepa_overlay_creditoridentificationnumber_span'>&nbsp;</span></td>
			</tr>
			<tr>
				<td>{$nn_lang.sepa_mandate_reference}</td><td>:</td><td><span id='sepa_overlay_mandatereference_span'>&nbsp;</span></td>
			</tr>
		</table><br/>
		{$nn_lang.sepa_mandate_paragraph}
		<br/><br/>
		<table>
			<tr>
				<td>{$nn_lang.sepa_mandate_name}</td><td>:</td><td span id="sepa_holder_name_span"></td>
			</tr>
			<tr id='sepa_company_display'>
				<td>{$nn_lang.sepa_mandate_company}</td><td>:</td><td>{$sepa_holder_company}</td>
			</tr>
			<tr>
				<td>{$nn_lang.sepa_mandate_address}</td><td>:</td><td>{$sepa_holder_address}</td>
			</tr>
			<tr>
				<td>{$nn_lang.sepa_mandate_pincode}</td><td>:</td><td>{$sepa_holder_zip} {$sepa_holder_city}</td>
			</tr>
			<tr>
				<td>{$nn_lang.sepa_mandate_country}</td><td>:</td><td><span id='sepa_overlay_country_span'>&nbsp;</span></td>
			</tr>
			<tr>
				<td>E-Mail</td><td>:</td><td>{$sepa_holder_mail}</td>
			</tr>
			<tr id='nn_sepa_overlay_iban_tr'>
				<td>IBAN</td><td>:</td><td><span id='sepa_overlay_iban_span'>&nbsp;</span></td>
			</tr>
			<tr id='nn_sepa_overlay_bic_tr'>
				<td>BIC</td><td>:</td><td><span id='sepa_overlay_bic_span'>&nbsp;</span></td>
			</tr>
		</table>
		<br/>
		{$sepa_holder_city}, <span id='sepa_overlay_mandatedate_span'>&nbsp;</span>,
		<span id="sepa_mandate_name_span"></span>
	</div>
	<div class='nn_footer'>
		<span class='mandate_confirm_btn' onclick='confirm_mandate_overlay();' id='mandate_confirm_btn' style='text-align:center;'>{$nn_lang.sepa_mandate_confirm_btn}</span>
		<span class='mandate_confirm_btn' onclick='close_mandate_overlay_on_cancel();' id='mandate_cancel_btn' style='text-align:center;'>{$nn_lang.sepa_mandate_close_btn}</span>
		<img src='{$filePath}/img/logo.png' alt='Novalnet AG' style='float:right;margin-right:1%' />
	</div>
</div>
<!-- SEPA MANDATE END-->
