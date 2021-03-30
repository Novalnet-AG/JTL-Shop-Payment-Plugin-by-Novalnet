<div class="container form">
  <fieldset>
    <legend>{$payment_name}</legend>
      <div id='cc_javascript_enable' style='display:block;'>
	 <strong>{$nn_lang.javascript_error}</strong>
      </div>
      <div id='nn_payment_cc' style='display:none;'>
	<input id="nn_payment" name="payment" type="hidden" value="novalnet_cc" />
	<div id="nn_loader" style="display:none">
	<style type="text/css">
	{literal}
		#nn_loader {
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
	</div>
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

	{$nn_lang.credit_card_desc}
	{$lang_cc_test_mode_info}
	<br><br>
    	<table style="width:50%">
      	     <tr>
	         <td valign="top"> {$nn_lang.credit_card_type}<span style='color:red'>*</span></td>
			<td valign="top">
				<select id="nn_type" name="nn_type">
					<option value = '' selected>{$nn_lang.select_type}</option>
					<option value="VI">Visa</option>
					<option value="MC">Mastercard</option>
					{if $cc_amex_accept}
						<option value="AE">AMEX</option>
					{/if}
				</select>
			</td>
      	     </tr>
      	     <tr>
	         <td valign="top"> {$nn_lang.credit_card_name}<span style='color:red'>*</span></td>
        	 <td valign="top"><input type="text" name="nn_holdername" id="nn_holdername" autocomplete="off" value="{$cc_name}" onkeypress="return isNumberKey(event, 'owner');"/> </td>
             </tr>
      	     <tr>
         	<td valign="top"> {$nn_lang.credit_card_number}<span style='color:red'>*</span></td>
	        <td valign="top"><input type="text" id="nn_cardnumber" autocomplete="off" onkeypress="return isNumberKey(event)" value="" /> </td>
      	    </tr>
     	    <tr>
	        <td valign="top"> {$nn_lang.credit_card_date}<span style='color:red'>*</span></td>
		<td valign="top">
			<select id="nn_expmonth" name="nn_expmonth">
				<option value = '' selected>{$nn_lang.credit_card_month}</option>
					<option value="01">01</option>
					<option value="02">02</option>
					<option value="03">03</option>
					<option value="04">04</option>
					<option value="05">05</option>
					<option value="06">06</option>
					<option value="07">07</option>
					<option value="08">08</option>
					<option value="09">09</option>
					<option value="10">10</option>
					<option value="11">11</option>
					<option value="12">12</option>
			</select>
			<select id="nn_expyear" name="nn_expyear">
				<option value = '' selected>{$nn_lang.credit_card_year}</option>
					{foreach name = ccyear from = $cc_year_limit item = acc_year_limit }
						<option value = {$cc_year_limit.$acc_year_limit} > {$acc_year_limit} </option>
					{/foreach}
			</select>
		</td>
      	    </tr>
      	    <tr>
	         <td valign="top"> {$nn_lang.credit_card_cvc}<span style='color:red'>*</span></td>
        	 <td valign="top"><input type="text" name="nn_cvvnumber" id= "nn_cvvnumber" autocomplete="off" size="3" onkeypress="return isNumberKey(event)" />&nbsp;

         	<span id="showcvc"><a onmouseover="show_cvc_info(true);" onmouseout="show_cvc_info(false);" style="text-decoration: none;"><img src="{$filePath}/img/cvc_hint.png" border="0" style="margin-top:4px;" alt="CCV/CVC?"></a></span>
         <span id="cvc_info" style="display:none;"><img src="{$filePath}/img/kreditkarte_cvc.png"></span></td>
      	    </tr>
      {if $pin_by_callback}
      	   <tr>
        	 <td valign="top"> {$nn_lang.callback_phone_number}<span style='color:red'>*</span></td>
	         <td valign="top"><input type="text" name="nn_telnumber" id="nn_telnumber" autocomplete="off"/> </td>
      	  </tr>
	      <input type="hidden" id="nn_tele_error_message" value="{$nn_lang.callback_telephone_error}">
      {elseif $pin_by_sms}
      	  <tr>
         	<td valign="top"> {$nn_lang.callback_sms}<span style='color:red'>*</span></td>
         	<td valign="top"><input type="text" name="nn_mob_number" id="nn_mob_number" autocomplete="off"/> </td>
          </tr>
     	     <input type="hidden" id="nn_mob_error_message" value="{$nn_lang.callback_mobile_error}">
      {elseif $reply_by_email}
          <tr>
         	<td valign="top"> {$nn_lang.callback_mail}<span style='color:red'>*</span></td>
	         <td valign="top"><input type="text" name="nn_mail" id="nn_mail" autocomplete="off" value="{$lang_cc_customer_email}"/> </td>
          </tr>
	     <input type="hidden" id="nn_mail_error_message" value="{$nn_lang.callback_email_pin}">
      {/if}

	  <input id="novalnet_vendor" type="hidden" value="{$vendor_id}">
	  <input id="novalnet_authcode" type="hidden" value="{$auth_code}">
	  <input id="cc_pan_hash" name="cc_pan_hash" type="hidden" value="" />
	  <input id="nn_unique_id" name="nn_unique_id" type="hidden" value="{$uniq_value}" />
	  <input id="nn_cc_input_panhash" name="nn_cc_input_panhash" type="hidden" value="{$nn_hash}" />
	  <input id="nn_cc_valid_error_ccmessage" type="hidden" value="{$nn_lang.credit_card_error}" />
	  <input id="nn_merchant_valid_error_ccmessage" type="hidden" value="{$nn_lang.merchant_error}" />
    </table>
    {/if}
  </div>
<script type = "text/javascript"  src="{$filePath}/js/novalnet_cc.js" ></script>
</fieldset>
</div>
