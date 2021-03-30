/*
 * Novalnet Direct Debit SEPA Script
 * By Novalnet AG (http://www.novalnet.de)
 * Copyright (c) Novalnet AG
*/
if(typeof(jQuery) == 'undefined') {
	var s = document.createElement("script");
	s.type = "text/javascript";
	s.src = "/includes/plugins/novalnetag/version/1000/paymentmethod/js/jquery.js";
	document.getElementsByTagName("head")[0].appendChild(s);
}

jQuery(document).ready(function() {
	document.getElementById('nn_payment_sepa').style.display = 'block';
	document.getElementById('sepa_javascript_enable').style.display = 'none';
	var formid = jQuery('#nn_payment').closest('form').attr('id');

	jQuery('#'+formid).submit(function (evt) {
		var selected_payment = jQuery("input[name='payment']").attr('type') == 'hidden' ? jQuery("input[name='payment']").val() : jQuery("input[name='payment']:checked").val();

		if(selected_payment != 'novalnet_sepa') { return true; }

		if(document.getElementById('nn_pin')){nn_pin = document.getElementById('nn_pin').value.replace(/^\s+|\s+$/g, '');}

		if(typeof nn_pin != 'undefined'){
			if(nn_pin == '' && !(document.getElementById('nn_forgot_pin').checked)){
				alert(jQuery('#nn_pin_empty_error_message').val());
				return false;
			}
			else if(validateSpecialChars(nn_pin) && !(document.getElementById('nn_forgot_pin').checked)){
				alert(jQuery('#nn_pin_error_message').val());
				return false;
			}
		}

		if(document.getElementById("nn_sepa_mandate_confirm") && document.getElementById("nn_sepa_mandate_confirm").checked == false){
			alert(jQuery('#nn_lang_mandate_confirm').val());
			return false;
		}

		if(document.getElementById('nn_telnumber') && (document.getElementById('nn_telnumber').value.replace(/^\s+|\s+$/g, '') == '' || isNaN(document.getElementById('nn_telnumber').value))){
			alert(jQuery('#nn_tele_error_message').val());
			return false;
		}

		if(document.getElementById('nn_mob_number') && (document.getElementById('nn_mob_number').value.replace(/^\s+|\s+$/g, '') == '' || isNaN(document.getElementById('nn_mob_number').value))){
			alert(jQuery('#nn_mob_error_message').val());
			return false;
		}

		if(document.getElementById('nn_mail'))
			var email = document.getElementById('nn_mail').value;

		if(email && (email.trim().replace(/^\s+|\s+$/g, '') == '' || !(/^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/).test(email.trim()))){
			alert($('#nn_mail_error_message').val());
			return false
		}
	});

	jQuery('#nn_sepa_mandate_confirm').click(function() {
		if(!jQuery("#nn_sepa_mandate_confirm").is(":checked")){
			sepa_mandate_unconfirm_process();
		}else{
			sepaibanbiccall();
		}
	});

	jQuery('#nn_sepaowner,#nn_sepa_account_no,#nn_sepa_country,#nn_sepa_bank_code').change(function() {
		sepa_mandate_unconfirm_process();
	});

});

function sepa_mandate_unconfirm_process()
{
	jQuery("#nn_sepa_mandate_confirm").attr("checked",false);
	document.getElementById('nn_sepapanhash').value = '';
	document.getElementById('sepa_mandate_ref').value = '';
	document.getElementById('sepa_mandate_date').value = '';
	jQuery('#novalnet_sepa_iban_span').html('');
	jQuery('#novalnet_sepa_bic_span').html('');
}

function validateSpecialChars(input_val)
{
	var pattern = /^\s+|\s+$|([\/\\#,+@!^()$~%.":*?<>{}])/g;
	return pattern.test(input_val);
}

function removeUnwantedSpecialChars(input_val)
{
  return input_val.replace(/^\s+|\s+$|([\/\\#,@+!^()$~%_.":*?<>{}])/g,'');
}

function isAlphanumeric(event)
{
	var keycode = ( 'which' in event ) ? event.which : event.keyCode;
	event = event || window.event;
    var reg = ( ( event.target || event.srcElement ).id == 'nn_sepaowner' ) ? /^[a-z0-9&\s]+$/i : /^[a-z0-9]+$/i;
    return ( reg.test( String.fromCharCode( keycode ) ) || keycode == 0 || keycode == 8 );
}

function sepahashrequestcall()
{
	var bank_country = "";var account_holder = "";var account_no = "";
	var iban = "";var bic = "";var bank_code = "";var nn_sepa_uniqueid = "";
	var nn_vendor = "";var nn_auth_code = "";var mandate_confirm = 0;

	if(document.getElementById('nn_sepa_country')) {bank_country = document.getElementById('nn_sepa_country').value;}
	if(document.getElementById('nn_sepaowner')) {account_holder = removeUnwantedSpecialChars(document.getElementById('nn_sepaowner').value);}
	if(document.getElementById('nn_sepa_account_no')){iban = document.getElementById('nn_sepa_account_no').value;}
	if(document.getElementById('nn_sepa_bank_code')){bic = document.getElementById('nn_sepa_bank_code').value;}
	if(document.getElementById('nn_sepa_iban')){nn_sepa_iban = document.getElementById('nn_sepa_iban').value;}
	if(document.getElementById('nn_sepa_bic')){nn_sepa_bic = document.getElementById('nn_sepa_bic').value;}
	if(document.getElementById('nn_vendor')){nn_vendor = document.getElementById('nn_vendor').value;}
	if(document.getElementById('nn_authcode')){nn_auth_code = document.getElementById('nn_authcode').value;}
	if(document.getElementById('nn_sepaunique_id')){nn_sepa_uniqueid = document.getElementById('nn_sepaunique_id').value;}

	if(nn_vendor == '' || nn_auth_code == '') {alert(jQuery('#nn_lang_valid_merchant_credentials').val()); sepa_mandate_unconfirm_process(); return false;}

	if(bank_country == '' || account_holder == '' || iban == '' || nn_vendor == '' || nn_auth_code == '' || nn_sepa_uniqueid == '') {
		alert(jQuery('#nn_lang_valid_account_details').val());sepa_mandate_unconfirm_process(); return false;
	}
	if(bic == '')
	{
		if(bank_country == 'DE' && isNaN(iban))
		{
			bic = '123456';
		}
		else{
			alert(jQuery('#nn_lang_valid_account_details').val());
			sepa_mandate_unconfirm_process();
			return false;
		}
	}

	if (!isNaN(iban) && !isNaN(bic))  {
		account_no = iban;
		bank_code = bic;
		iban = bic = '';
	}
	if (nn_sepa_iban != '' && nn_sepa_bic != '')  {
		iban = nn_sepa_iban;
        bic = nn_sepa_bic;
	}
	jQuery('#nn_loader').css({display:'block'});
	jQuery('#nn_loader').attr('tabIndex',-1).focus();
	var nnurl_val = {'account_holder' : account_holder , 'bank_account' : account_no , 'bank_code' : bank_code, 'vendor_id' : nn_vendor, 'vendor_authcode' : nn_auth_code, 'bank_country' : bank_country, 'unique_id' : nn_sepa_uniqueid, 'sepa_data_approved' : 1, 'mandate_data_req' : 1 , 'iban' : iban , 'bic' : bic };

	var url = getUrlValue();
	// IE8 & 9 only Cross domain JSON GET request
	if ('XDomainRequest' in window && window.XDomainRequest !== null) {
        var xdr = new XDomainRequest(); // Use Microsoft XDR
        nnurl_val = jQuery.param(nnurl_val);
		xdr.open('POST',url);
        xdr.onload = function (){
			// XDomainRequest doesn't provide responseXml, so if you need it:
			getSepaHashResultHandle(jQuery.parseJSON(this.responseText));
        };
        xdr.onerror = function() {
            _result = false;
        };
        xdr.send(nnurl_val);
	}else{
		jQuery.ajax({
			type: 'POST',
			url : url,
			data: nnurl_val,
			dataType: 'json',
			success: function(data) {
				getSepaHashResultHandle(data);
			}
		});
	}
}
function getSepaHashResultHandle(data)
{
	if(data.hash_result == 'success')
	{
		document.getElementById('nn_sepapanhash').value = data.sepa_hash;
		document.getElementById('nn_sepapanhash').disabled = false;
		document.getElementById('sepa_mandate_ref').value = data.mandate_ref;
		document.getElementById('sepa_mandate_ref').disabled = false;
		document.getElementById('sepa_mandate_date').value = data.mandate_date;
		document.getElementById('sepa_mandate_date').disabled = false;
		jQuery('#nn_loader').css({display:'none'});
		show_mandate_overlay();
	}else{
		alert(dataobj.hash_result);
		jQuery('.bgCover').css( {display:'none'} );
		jQuery('#nn_loader').css( {display:'none'} );
		return false;
	}
}
function sepaibanbiccall()
{
	var bank_country = "";var account_holder = "";var account_no = "";
	var bank_code = "";var nn_sepa_uniqueid = "";
	var nn_vendor = "";var nn_auth_code = "";
	if(document.getElementById('nn_sepa_country')) {bank_country = document.getElementById('nn_sepa_country').value;}
	if(document.getElementById('nn_sepaowner')) {account_holder = removeUnwantedSpecialChars(document.getElementById('nn_sepaowner').value);}
	if(document.getElementById('nn_sepa_account_no')){account_no = document.getElementById('nn_sepa_account_no').value;}
	if(document.getElementById('nn_sepa_bank_code')){bank_code = document.getElementById('nn_sepa_bank_code').value;}
	if(document.getElementById('nn_vendor')){nn_vendor = document.getElementById('nn_vendor').value;}
	if(document.getElementById('nn_authcode')){nn_auth_code = document.getElementById('nn_authcode').value;}
	if(document.getElementById('nn_sepaunique_id')){nn_sepa_uniqueid = document.getElementById('nn_sepaunique_id').value;}
	document.getElementById('nn_sepa_iban').value = '';
	document.getElementById('nn_sepa_bic').value = '';

	if(isNaN(account_no) && isNaN(bank_code))
	{
		jQuery('#novalnet_sepa_iban_span').html('');
		jQuery('#novalnet_sepa_bic_span').html('');
		sepahashrequestcall();
		return false;
	}
	if(bank_code == '' && isNaN(account_no)) {
		sepahashrequestcall();
		return false;
	}

	if (nn_vendor == '' || nn_auth_code == '') {
		alert(jQuery('#nn_lang_valid_merchant_credentials').val());
		return false;
	}

	if (bank_country == '' || account_holder == '' || account_no == '' || bank_code == '' || nn_vendor == '' || nn_auth_code == '' || nn_sepa_uniqueid == '' || isNaN(bank_code) || isNaN(account_no)) {
		alert(jQuery('#nn_lang_valid_account_details').val());
		sepa_mandate_unconfirm_process();
		return false;
	}
	jQuery('.bgCover').css({
		display:'block',
		width: jQuery(document).width(),
		height: jQuery(document).height()
	});
	jQuery('.bgCover').css({opacity:0}).animate( {opacity:0.5, backgroundColor:'#878787'} );
	jQuery('#nn_loader').css({display:'block'});
	jQuery('#nn_loader').attr('tabIndex',-1).focus();
	var nnurl_val = {'account_holder' : account_holder , 'bank_account' : account_no , 'bank_code' : bank_code, 'vendor_id' : nn_vendor, 'vendor_authcode' : nn_auth_code, 'bank_country' : bank_country, 'unique_id' : nn_sepa_uniqueid, 'get_iban_bic' : 1};
	var url = getUrlValue();
	// IE8 & 9 only Cross domain JSON GET request
	if ('XDomainRequest' in window && window.XDomainRequest !== null) {
        var xdr = new XDomainRequest(); // Use Microsoft XDR
        nnurl_val = jQuery.param(nnurl_val);
		xdr.open('POST', url);
		xdr.onload = function (){
			getSepaHashResult(jQuery.parseJSON(this.responseText));
        };
        xdr.onerror = function() {
            _result = false;
        };
        xdr.send(nnurl_val);
	}else{
		jQuery.ajax({
			type: 'POST',
			url : url,
			data: nnurl_val,
			dataType: 'json',
			success: function(data) {
				getSepaHashResult(data);
			}
		});
	}
}

function show_mandate_overlay()
{
	document.onkeydown = function(evt){
		var charCode = (evt.which) ? evt.which : evt.keyCode;
			if ((evt.ctrlKey == true && charCode == 114) || charCode == 116 ) {
				return true;
			}
		return false;
	};
	jQuery('#headlinks').css({display:'none'});

	if((jQuery('#nn_sepa_bank_code').val() == '' && jQuery('#nn_sepa_country').val() != 'DE')|| jQuery('#nn_sepa_account_no').val() == '' || jQuery('#nn_sepaowner').val() == '' || jQuery('#sepa_mandate_date').val() == '' || jQuery('#sepa_mandate_ref').val() == '')
	{
		alert(jQuery('#nn_lang_valid_account_details').val());
		return false;
	}
	jQuery('.bgCover').css({
		display:'block',
		width: jQuery(document).width(),
		height: jQuery(document).height()
	});
	jQuery('.bgCover').css({opacity:0}).animate( {opacity:0.5, backgroundColor:'#878787'} );
	var template_iban = ''; var template_bic = '';
	if(isNaN(jQuery('#nn_sepa_account_no').val())) {
		template_iban = jQuery('#nn_sepa_account_no').val();
	} else {
		template_iban = jQuery('#nn_sepa_iban').val();
	}
	if(isNaN(jQuery('#nn_sepa_bank_code').val())) {
		template_bic = jQuery('#nn_sepa_bank_code').val();
	} else {
		template_bic = jQuery('#nn_sepa_bic').val();
	}

	jQuery('#sepa_overlay_iban_span').html(template_iban);
	if(template_bic != '')
	{
		jQuery('#sepa_overlay_bic_span').html(template_bic);
		jQuery('#nn_sepa_overlay_bic_tr').show(60);
	} else {
		jQuery('#sepa_overlay_bic_span').html('');
		jQuery('#nn_sepa_overlay_bic_tr').hide(60);
	}
	jQuery('#sepa_overlay_payee_span').html('Novalnet AG');
	jQuery('#sepa_overlay_creditoridentificationnumber_span').html('DE53ZZZ00000004253');
	jQuery('#sepa_holder_name_span').html(removeUnwantedSpecialChars(jQuery('#nn_sepaowner').val()));
	jQuery('#sepa_overlay_country_span').html(jQuery('#nn_sepa_country option:selected').text());
	jQuery('#sepa_overlay_mandatedate_span').html(normalizeDate(jQuery('#sepa_mandate_date').val()));
	jQuery('#sepa_overlay_mandatereference_span').html(jQuery('#sepa_mandate_ref').val());
	jQuery('#sepa_mandate_name_span').html(removeUnwantedSpecialChars(jQuery('#nn_sepaowner').val()));
	jQuery('#sepa_mandate_overlay_block_first').css({ display:'none', position:'fixed' });
	jQuery('#sepa_mandate_overlay_block').css({ display:'block', position:'fixed' });
	if(jQuery('#sepa_company').val() =='')
        jQuery('#sepa_company_display').css({ display:'none'});

	if(jQuery(window).width() < 650) {
		jQuery('#sepa_mandate_overlay_block').css({left:(jQuery(window).width()/2),top:(jQuery(window).height()/2),width:0,height:0}).animate( {left:(( jQuery(window).width() - (jQuery(window).width() - 10) )/2),top:5,width:(jQuery(window).width() - 10),height:(jQuery(window).height()-10)} );
		jQuery('#overlay_window_block_body').css({'height':(jQuery(window).height()-95)});
	} else {
        jQuery('#sepa_mandate_overlay_block').css( {left:((jQuery(window).height()-(490/2))),top:((jQuery(window).height())*0.07),width:('40%'),height:('75%')} );
		jQuery('#overlay_window_block_body').css({'height':('70%')});
	}
	return true;
}

function confirm_mandate_overlay()
{
	jQuery("#nn_sepa_mandate_confirm").attr("checked",true);
	close_mandate_overlay();
}

//Close confirmation overlay when clicking the close button image
function close_mandate_overlay()
{
	document.onkeydown = null;
	jQuery('#sepa_mandate_overlay_block').hide(60);
	jQuery('#headlinks').css({display:'block'});
	jQuery('.bgCover').css( {display:'none'} );
	return true;
}

function close_mandate_overlay_on_cancel() {
  document.onkeydown = null;
  sepa_mandate_unconfirm_process();
  jQuery('#sepa_mandate_overlay_block').hide(60);
  jQuery('#headlinks').css({display:'block'});
  jQuery('.bgCover').css( {display:'none'} );
  return true;
}

function normalizeDate(input) {
	if(typeof input != 'undefined' && input != '') {
		var parts = input.split('-');
		return (parts[2] < 10 ? '0' : '') + parseInt(parts[2]) + '.'
			+ (parts[1] < 10 ? '0' : '') + parseInt(parts[1]) + '.'
			+ parseInt(parts[0]);
	}
}

function getSepaHashResult(data)
{
	if(data.hash_result == 'success')
	{
		document.getElementById('nn_sepa_iban').value = data.IBAN;
		document.getElementById('nn_sepa_bic').value = data.BIC;
		if(data.IBAN != '')
		{
			jQuery('#novalnet_sepa_iban_span').html('<b>IBAN:</b> '+data.IBAN);
			jQuery('#nn_sepa_overlay_iban_tr').show(60);
		} else {
			jQuery('#nn_sepa_overlay_iban_tr').hide(60);
		}
		if(data.BIC != '')
		{
			jQuery('#novalnet_sepa_bic_span').html('<b>BIC:</b> '+data.BIC);
			jQuery('#nn_sepa_overlay_bic_tr').show(60);
		}
		else{
			jQuery('#nn_sepa_overlay_bic_tr').hide();
			jQuery('#nn_loader').css({display:'none'});
			jQuery('.bgCover').css( {display:'none'} );
			alert(jQuery('#nn_lang_valid_account_details').val());
			sepa_mandate_unconfirm_process();
			return false;
		}
	}else{
		alert(dataobj.hash_result);
		jQuery('#nn_loader').css( { display:'none'} );
		jQuery('.bgCover').css( {display:'none'} );
		return false;
	}
	sepahashrequestcall();
	return true;
}
// AJAX call for refill sepa form elements
function separefillformcall()
{
	var refillpanhash = '';
    if (document.getElementById('nn_sepa_input_panhash')) {
        refillpanhash = document.getElementById('nn_sepa_input_panhash').value;
    }
    if (refillpanhash == '' || typeof refillpanhash == 'undefined') {
        return false;
    }
    var nn_vendor = "";
    var nn_auth_code = "";
    var nn_uniqueid = "";
    if(document.getElementById('nn_vendor')){nn_vendor = document.getElementById('nn_vendor').value;}
	if(document.getElementById('nn_authcode')){nn_authcode = document.getElementById('nn_authcode').value;}
	if(document.getElementById('nn_sepaunique_id')){nn_sepaunique_id = document.getElementById('nn_sepaunique_id').value;}
	if(nn_vendor == '' || nn_authcode == '' || nn_sepaunique_id == '') {return false;}
	jQuery('.bgCover').css({
		display:'block',
		width: jQuery(document).width(),
		height: jQuery(document).height()
	});
	jQuery('.bgCover').css({opacity:0}).animate( {opacity:0.5, backgroundColor:'#878787'} );
	document.getElementById('nn_loader').style.display='block';
	var nnurl_val = "vendor_id="+nn_vendor+"&vendor_authcode="+nn_authcode+"&unique_id="+nn_sepaunique_id+"&sepa_data_approved=1&mandate_data_req=1&sepa_hash="+refillpanhash;
	var url = getUrlValue();
		if ('XDomainRequest' in window && window.XDomainRequest !== null) {
			var xdr = new XDomainRequest(); // Use Microsoft XDR
			xdr.open('POST',url);
			xdr.onload = function () {
				refillResponse(jQuery.parseJSON(this.responseText));
			};
			xdr.onerror = function() {
						_result = false;
					};

			xdr.send(nnurl_val);
		}else{
			jQuery.ajax({
				type: 'POST',
				url : url,
				data: nnurl_val,
				dataType: 'json',
				success: function(data) {
					refillResponse(data);
				}
			});
		}
}

function getUrlValue()
{
	var url = location.href;
	var urlArr = url.split('://');
	var url_prefix = ((urlArr[0] != '' && urlArr[0] == 'https') ? 'https' : 'http' );
	var url_val  = url_prefix+"://payport.novalnet.de/sepa_iban";

	return url_val;
}

function refillResponse(data)
{
	if (data.hash_result == "success")
	{
		jQuery('.bgCover').css( {display:'none'} );
		jQuery('#nn_loader').css( {display:'none'} );
		var hash_string = data.hash_string.split('&');
		var arrayResult={};
		for (var i=0,len=hash_string.length;i<len;i++)
		{
			if(hash_string[i]=='' || hash_string[i].indexOf("=") == -1)
			{
				hash_string[i] = hash_string[i-1] +'&'+hash_string[i];
			}
			var hash_result_val = hash_string[i].split('=');
			arrayResult[hash_result_val[0]] = hash_result_val[1];
		}
			jQuery('#nn_sepaowner').val(arrayResult.account_holder);
			jQuery('#nn_sepa_country').val(arrayResult.bank_country);
				jQuery('#nn_sepa_country').change();
			jQuery('#nn_sepa_account_no').val(arrayResult.iban);
			if (arrayResult.bic != '123456')
				jQuery('#nn_sepa_bank_code').val(arrayResult.bic);
	}
}
separefillformcall();
