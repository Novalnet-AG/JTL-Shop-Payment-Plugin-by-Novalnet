/*
 * Novalnet Credit Card Script
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
	document.getElementById('nn_payment_cc').style.display = 'block';
	document.getElementById('cc_javascript_enable').style.display = 'none';
	var formid = jQuery('#nn_payment').closest('form').attr('id');
	jQuery('#'+formid).submit(function (evt) {

	var selected_payment = jQuery("input[name='payment']").attr('type') == 'hidden' ? jQuery("input[name='payment']").val() : jQuery("input[name='payment']:checked").val();

		if(selected_payment != 'novalnet_cc') {
			 return true;
		}

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
		if(document.getElementById('cc_pan_hash') && document.getElementById('cc_pan_hash').value == '')
		{
			evt.preventDefault();
			cchashcall();
		}

	});
});

function cchashcall()
{
	var cc_type = "";var cc_holder = "";var cc_no = "";
	var cc_exp_month = "";var cc_exp_year = "";var cc_cvc = "";
	var nn_vendor = "";var nn_auth_code = "";var nn_cc_uniqueid = "";
	if(document.getElementById('nn_type')) {cc_type = document.getElementById('nn_type').value;}
	if(document.getElementById('nn_holdername')) {cc_holder = removeUnwantedSpecialCharsCC(document.getElementById('nn_holdername').value).replace(/^\s+|\s+$/g, '');}
	if(document.getElementById('nn_cardnumber')){cc_no = document.getElementById('nn_cardnumber').value.replace(/^\s+|\s+$/g, '');}
	if(document.getElementById('nn_expmonth')){cc_exp_month = document.getElementById('nn_expmonth').value;}
	if(document.getElementById('nn_expyear')){cc_exp_year = document.getElementById('nn_expyear').value;}
	if(document.getElementById('nn_cvvnumber')){cc_cvc = document.getElementById('nn_cvvnumber').value;}
	if(document.getElementById('novalnet_vendor')){nn_vendor = document.getElementById('novalnet_vendor').value;}
	if(document.getElementById('novalnet_authcode')){nn_auth_code = document.getElementById('novalnet_authcode').value;}
	if(document.getElementById('nn_unique_id')){nn_cc_uniqueid = document.getElementById('nn_unique_id').value;}
	if(document.getElementById('nn_telnumber')){nn_tel_number = document.getElementById('nn_telnumber').value.replace(/^\s+|\s+$/g, '');}
	if(document.getElementById('nn_mob_number')){nn_mob_number = document.getElementById('nn_mob_number').value.replace(/^\s+|\s+$/g, '');}
	if(document.getElementById('nn_mail')){nn_mail = document.getElementById('nn_mail').value.replace(/^\s+|\s+$/g, '');}

	if(nn_vendor == '' || nn_auth_code == '') {
		alert(jQuery('#nn_merchant_valid_error_ccmessage').val());
		return false;
	}

	var currentDateVal = new Date();
	var regularMail = /^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
	if((cc_type == '' || cc_holder == '' || cc_no == '' || cc_cvc == '') || cc_exp_month == '' || cc_exp_year == '' || (cc_exp_year == currentDateVal.getFullYear() && cc_exp_month < (currentDateVal.getMonth()+1))) {
		alert(jQuery('#nn_cc_valid_error_ccmessage').val());
		return false;
	}

	if(typeof nn_tel_number != 'undefined' && (nn_tel_number == '' || isNaN(nn_tel_number))){
			alert(jQuery('#nn_tele_error_message').val());
			return false;
	}
	else if(typeof nn_mob_number != 'undefined' && (nn_mob_number == '' || isNaN(nn_mob_number))){
			alert(jQuery('#nn_mob_error_message').val());
			return false;
	}
	else if(typeof nn_mail != 'undefined' && (nn_mail == '' || !regularMail.test(nn_mail))){
		alert(jQuery('#nn_mail_error_message').val());
		return false
	}

	document.getElementById('nn_loader').style.display='block';
	var nnurl_val = {'noval_cc_exp_month' : cc_exp_month , 'noval_cc_exp_year' : cc_exp_year , 'noval_cc_holder' : cc_holder, 'noval_cc_no' : cc_no, 'noval_cc_type' : cc_type, 'unique_id' : nn_cc_uniqueid, 'vendor_authcode' : nn_auth_code, 'vendor_id' : nn_vendor };
	ccAjaxCall(nnurl_val);
}

function ccAjaxCall(cc_hash_req)
{
	var url = getUrlValue();
	// IE8 & 9 only Cross domain JSON GET request
	if ('XDomainRequest' in window && window.XDomainRequest !== null) {
        var xdr = new XDomainRequest(); // Use Microsoft XDR
        cc_hash_req = jQuery.param(cc_hash_req);
		xdr.open('POST', url);
        xdr.onload = function (){
			getHashResult(jQuery.parseJSON(this.responseText));
        };
        xdr.onerror = function() {
            _result = false;
        };
        xdr.send(cc_hash_req);
	}else{
		jQuery.ajax({
			type: 'POST',
			url : url,
			data: cc_hash_req,
			dataType: 'json',
			success: function(data) {
				getHashResult(data);
			}
		});
	}
}

function show_cvc_info(key)
{
	if(key) {
		document.getElementById('cvc_info').style.display='block';
			var position= jQuery('#showcvc').position();
			jQuery('#cvc_info').css('position','absolute');
			jQuery('#cvc_info').css('top',(position.top-100));
			jQuery('#cvc_info').css('left',position.left+25);
			jQuery('#cvc_info').css('z-index','999');
	} else {
		document.getElementById('cvc_info').style.display='none';
	}
}

function removeUnwantedSpecialCharsCC(input_val) {
     return input_val.replace(/[\/\\|\]\[|#,+()$@'~%."`:;*?<>!^{}=_-]/g,'');
}

function validateSpecialChars(input_val)
{
	var pattern = /^\s+|\s+$|([\/\\#,+@!^()$~%.":*?<>{}])/g;
	return pattern.test(input_val);
}

function isNumberKey(event, allowstring)
{
	var keycode = ( 'which' in event ) ? event.which : event.keyCode;
	event = event || window.event;
	var reg = (allowstring == 'owner') ? /^(?:[A-Za-z0-9&\s]+$)/ : ((( event.target || event.srcElement ).id == 'nn_cardnumber' ) ? /^[0-9\s]+$/i : /^[0-9]+$/i);
    return ( reg.test( String.fromCharCode( keycode ) ) || keycode == 0 || keycode == 8 || (event.ctrlKey == true && keycode == 114));
}

function getHashResult(dataobj)
{
	if(dataobj.hash_result == 'success')
	{
		document.getElementById('cc_pan_hash').value = dataobj.pan_hash;
		document.getElementById('cc_pan_hash').disabled = false;
		document.getElementById('nn_unique_id').value = dataobj.unique_id;
		document.getElementById('nn_unique_id').disabled = false;
		jQuery('#nn_holdername').closest('form').submit();
	}
	else{
		alert(dataobj.hash_result);
		document.getElementById('nn_loader').style.display='none';
		return false;
	}
}


function ccRefillCall()
{
	var cc_panhash = '';
	if(document.getElementById('nn_cc_input_panhash')){cc_panhash = document.getElementById('nn_cc_input_panhash').value;}
	if(cc_panhash == '' || typeof cc_panhash == 'undefined') {return false;}
	if(document.getElementById('novalnet_vendor')){nn_vendor = document.getElementById('novalnet_vendor').value;}
	if(document.getElementById('novalnet_authcode')){nn_auth_code = document.getElementById('novalnet_authcode').value;}
	if(document.getElementById('nn_unique_id')){nn_cc_uniqueid = document.getElementById('nn_unique_id').value;}
	if(nn_vendor == '' || nn_auth_code == '' || nn_cc_uniqueid == '' ) {return false;}
	document.getElementById('nn_loader').style.display='block';
	var nnurl_val = {'pan_hash' : cc_panhash ,'unique_id' : nn_cc_uniqueid , 'vendor_authcode' : nn_auth_code , 'vendor_id' : nn_vendor};
	var url = getUrlValue();

	// IE8 & 9 only Cross domain JSON GET request
	if ('XDomainRequest' in window && window.XDomainRequest !== null) {
	var xdr = new XDomainRequest(); // Use Microsoft XDR
		nnurl_val = jQuery.param(nnurl_val);
		xdr.open('POST', url);
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
	var url_val  = url_prefix+"://payport.novalnet.de/payport_cc_pci";

	return url_val;
}

function refillResponse(data)
{
	if (data.hash_result == "success")
	{
		jQuery('#nn_loader').css({'display':'none'});
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
			jQuery('#nn_holdername').val(removeUnwantedSpecialCharsCC(arrayResult.cc_holder));
			jQuery('#nn_cardnumber').val(arrayResult.cc_no);

			var novalnet_cc_exp_month = arrayResult.cc_exp_month;
				novalnet_cc_exp_month = ((novalnet_cc_exp_month.length == 1) ? '0' + novalnet_cc_exp_month:novalnet_cc_exp_month);
					jQuery('#nn_expmonth').val(novalnet_cc_exp_month);
					jQuery('#nn_expmonth').change();
			jQuery('#nn_expyear').val(arrayResult.cc_exp_year);
			jQuery('#nn_expyear').change();
			jQuery('#nn_type').val(arrayResult.cc_type);
			jQuery('#nn_type').change();
	}
}
ccRefillCall();
