<div class="container form">
  <fieldset>
    <legend>{$payment_name}</legend>
    <input id="payment" name="payment" type="hidden" value="novalnet_invoice" />
    {$nn_lang.invoice_description}
    {$lang_invoice_test_mode_info}
    <br><br>
    {if $error}
	{if $error_desc}
	  	<p class="box_error">{$error_desc}</p>
	{/if}
    {elseif $pin_error}
	<table style="width:50%">
	    <tr>
	 	<td valign="top"> {$nn_lang.callback_pin} </td>
		<td valign="top"><input type="text" name="nn_pin" id="nn_pin" autocomplete="off" /> </td>
	    </tr>
		<input type="hidden" id="nn_pin_error_message" value="{$nn_lang.callback_pin_error}">
		<input type="hidden" id="nn_pin_empty_error_message" value="{$nn_lang.callback_pin_error_empty}">
	    <tr>
		<td></td><td valign="top"><input type="checkbox" name="nn_forgot_pin" id="nn_forgot_pin" /> {$nn_lang.callback_forgot_pin} </td>
	    </tr>
	</table>	
    {else}
	<table style="width:50%">
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
		<td valign="top"><input type="text" name="nn_mail" id="nn_mail" autocomplete="off" value="{$lang_invoice_customer_email}"/> </td>
	   </tr>
		<input type="hidden" id="nn_mail_error_message" value="{$nn_lang.callback_email_pin}">
	{/if}
	</table>
    {/if}
  </fieldset>
</div>
{literal}
	<script type = "text/javascript">
		$(document).ready(function(){
			$('.submit').click(function(e){
				nn_pin_validate(e);
			});
		});
		function nn_pin_validate(evt){
			if(document.getElementById('nn_telnumber')){
				nn_tel_number = document.getElementById('nn_telnumber').value.replace(/^\s+|\s+$/g, '');}
			if(document.getElementById('nn_mob_number')){
				nn_mob_number = document.getElementById('nn_mob_number').value.replace(/^\s+|\s+$/g, '');}
			if(document.getElementById('nn_mail')){
				nn_mail = document.getElementById('nn_mail').value.replace(/^\s+|\s+$/g, '');}
			if(document.getElementById('nn_pin')){
				nn_pin  = document.getElementById('nn_pin').value.replace(/^\s+|\s+$/g, '');}

			var regularMail = /^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
			if(typeof nn_tel_number != 'undefined' && (nn_tel_number == '' || isNaN(nn_tel_number))){
				alert(jQuery('#nn_tele_error_message').val());
				evt.preventDefault();
			}
			else if(typeof nn_mob_number != 'undefined' && (nn_mob_number == '' || isNaN(nn_mob_number))){
				alert(jQuery('#nn_mob_error_message').val());
				evt.preventDefault();
			}
			else if(typeof nn_mail != 'undefined' && (nn_mail == '' || !regularMail.test(nn_mail))){
				alert(jQuery('#nn_mail_error_message').val());
				evt.preventDefault();
			}
			else if(typeof nn_pin != 'undefined'){
				if(nn_pin == '' && !(document.getElementById('nn_forgot_pin').checked)){
					alert(jQuery('#nn_pin_empty_error_message').val());
					evt.preventDefault();
				}
				else if(validateSpecialChars(nn_pin) && !(document.getElementById('nn_forgot_pin').checked)){
					alert(jQuery('#nn_pin_error_message').val());
					evt.preventDefault();
				}
			}
		}

		function validateSpecialChars(input_val)
		{
			var pattern = /^\s+|\s+$|([\/\\#,+@!^()$~%.":*?<>{}])/g;
			return pattern.test(input_val);
		}
	</script>
{/literal}
