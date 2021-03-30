/*
 * Novalnet orders Script
 * By Novalnet AG (http://www.novalnet.de)
 * Copyright (c) Novalnet AG
*/
if(typeof(jQuery) == 'undefined') {
	var s = document.createElement("script");
	s.type = "text/javascript";
	s.src = "/includes/plugins/novalnetag/version/1000/paymentmethod/js/jquery.js";
	document.getElementsByTagName("head")[0].appendChild(s);
}

jQuery(document).ready(function(){

	jQuery('#transaction_details').addClass('active');

    jQuery('.nn_accordion_section_title').click(function(e) {

        var currentAttrValue = jQuery(this).attr('href');

        if(jQuery(e.target).is('.active')) {
            close_accordion_section();
        }else {
            close_accordion_section();

            jQuery(this).addClass('active');
            jQuery('.nn_accordion ' + currentAttrValue).slideDown(200);
        }

        e.preventDefault();
    });
	if(jQuery('#refund_amount_type_none').is(':checked')){
		jQuery('.refund_sepa').hide();
	};
	jQuery('#refund_amount_type_none').click(function(){
		jQuery('.refund_sepa').hide();
	});
	jQuery('#refund_amount_type_sepa').click(function(){
		jQuery('.refund_sepa').show();
	});
});

function close_accordion_section() {
	jQuery('.nn_accordion .nn_accordion_section_title').removeClass('active');
	jQuery('.nn_accordion .nn_accordion_section_content').slideUp(200);
}

function admin_order_display(order_no) {
	jQuery('.adminCover').css({
		display:'block',
		width: jQuery(document).width(),
		height: jQuery(document).height()
	});
	jQuery('.adminCover').css({opacity:0}).animate( {opacity:0.5, backgroundColor:'#878787'} );
	jQuery('#admin_order_display_block').css({ display:'block', position:'fixed' });

	if(jQuery(window).width() < 850)
	{
		jQuery('#admin_order_display_block').css({left:(jQuery(window).width()/2),top:(jQuery(window).height()/2),width:0,height:0}).animate( {left:(( jQuery(window).width() - (jQuery(window).width() - 10) )/2),top:5,width:(jQuery(window).width() - 10),height:(jQuery(window).height()-10)} );
		jQuery('#overlay_window_block_body').css({'height':(jQuery(window).height()-95)});
	} else {
		jQuery('#admin_order_display_block').css( {left:((jQuery(window).height())*0.40),top:((jQuery(window).height())*0.07),width:((jQuery(window).height())*1.50),height:'90%'} );

	}
	if ('XDomainRequest' in window && window.XDomainRequest !== null) {
		adminDisplayResponseCrossDomain(order_no);
	}
	else{
		adminDisplayResponse(order_no);
	}
	return true;
}

function trans_close_button() {
	jQuery('#admin_order_display_block').hide(60);
	jQuery('.adminCover').css( {display:'none'} );
	return true;
}

function captureval( api_orderno, capture_code ) {
	var params = {'orderNo' : api_orderno ,'apiStatus' : capture_code };
	ajax_call(params);
}

function amountupdate( api_orderno, amount_update ) {
	var amount_value = jQuery('#amount_update_val').val();
	if ( amount_value == '' || amount_value == 0){
		alert('Ungültiger Betrag');
		return false;
	}
	if (typeof jQuery('#duedate_update_val_days').val() != 'undefined')
	{
		var duedate_day_value = jQuery('#duedate_update_val_days').val();
		var duedate_month_value = jQuery('#duedate_update_val_month').val();
		var duedate_year_value = jQuery('#duedate_update_val_year').val();
		var duedate_value = duedate_year_value + '-' + duedate_month_value + '-' + duedate_day_value;
		var date = new Date();
		var current_day_value = ("0" + date.getDate()).slice(-2);
		var current_month_value = ("0" + (date.getMonth() + 1)).slice(-2);
		var current_year_value = date.getFullYear();
		var current_date_value = current_year_value + '-' + current_month_value + '-' + current_day_value;
		var due_date_formatted = duedate_day_value+'.'+duedate_month_value+'.'+duedate_year_value;

		if (!isDate(duedate_value)){
			alert ('Ungültiges Fälligkeitsdatum');
			return false;
		}
		
		if ( duedate_value < current_date_value ){
			alert ('Das Datum sollte in der Zukunft liegen');
			return false;
		}

		if( !confirm('Sind Sie sich sicher, dass Sie den Betrag / das Fälligkeitsdatum der Bestellung ändern wollen?')){
			return false;
		}

		if( duedate_day_value == null || duedate_month_value == null || duedate_year_value == null)
			duedate_value = '';
	}
	else if( !confirm('Sind Sie sich sicher, dass Sie den Bestellbetrag ändern wollen?') ){
			return false;
	}

	var params = {'orderNo' : api_orderno ,'apiStatus' : amount_update ,'amount' : amount_value ,'dueDateChange' : duedate_value};
	ajax_call(params);
}

function isDate(dueDate)
{
    if(dueDate == '')
        return false;

    var rxDatePattern = /^(\d{4})(\/|-)(\d{1,2})(\/|-)(\d{1,2})$/; //Declare Regex
    var dtArray = dueDate.match(rxDatePattern); // is format OK?
    if (dtArray == null)
        return false;

    dtMonth = dtArray[3];
    dtDay   = dtArray[5];
    dtYear  = dtArray[1];
    if (dtMonth < 1 || dtMonth > 12)
        return false;
    else if (dtDay < 1 || dtDay> 31)
        return false;
    else if ((dtMonth==4 || dtMonth==6 || dtMonth==9 || dtMonth==11) && dtDay ==31)
        return false;
    else if (dtMonth == 2)
    {
        var isleap = (dtYear % 4 == 0 && (dtYear % 100 != 0 || dtYear % 400 == 0));
        if (dtDay> 29 || (dtDay ==29 && !isleap))
                return false;
    }
    return true;
}

function refundval( api_orderno, refund_code ) {
	var refund_choice = jQuery('input[name=refund_amount_type]:checked').val();
	var sepa_holder = jQuery.trim(jQuery('#refund_account_holder_sepa').val());
	var sepa_accno = jQuery.trim(jQuery('#refund_account_no_sepa').val());
	var sepa_bankcode = jQuery.trim(jQuery('#refund_bank_code_sepa').val());
	var amount_refund = jQuery('#amount_refund_val').val();
	var refund_ref = jQuery.trim(jQuery('#refund_ref').val());
	var rem_amount = jQuery('#remaining_amount').val();
		if ( amount_refund == '' || amount_refund <= 0 || (parseFloat(amount_refund) > parseFloat(rem_amount))){
			alert('Ungültiger Betrag');
			return false;
		}
		if (refund_choice == 'nn_sepa')
		{
			if (sepa_holder == '' ||  sepa_accno == '' || sepa_bankcode == ''){
				alert('Ihre Kontodaten sind ungültig');
				return false;
			}
		}
		var params = {'orderNo' : api_orderno ,'apiStatus' : refund_code , 'refundMethod' : refund_choice, 'accountHolderSepa' : sepa_holder , 'accountNumberSepa' : sepa_accno , 'bankCodeSepa' : sepa_bankcode , 'refundAmount' : amount_refund, 'refundRef' : refund_ref};
		ajax_call(params);
}

function subscription_cancel( api_orderno , subs_code ) {
	var termination_reason = jQuery('#subscribe_termination_reason').val();
	if ( termination_reason == '' || termination_reason == null){
		alert('Wählen Sie bitte den Grund für die Abonnementskündigung aus');
		return false;
	}
	var params = {'orderNo' : api_orderno , 'apiStatus' : subs_code , 'subsReason' : termination_reason }
	ajax_call(params);
}

function ajax_call(params) {
	jQuery('.confirm').attr({'style':'color: #000000 !important; background-color: #878787 !important;','disabled':'disabled'});
	var currency = jQuery('#currency_type').val();
	setTimeout(function(){
		if ('XDomainRequest' in window && window.XDomainRequest !== null) {
			var xdr = new XDomainRequest(); // Use Microsoft XDR
			var query = jQuery.param(params);
			xdr.open('GET', 'novalnet_api.php?'+query);
			xdr.onload = function () {
				if (currency == 'EUR')
					alert(this.responseText.replace('&euro;','€'));
				else
					alert(this.responseText.replace('&dollar;','$'));
					adminDisplayResponseCrossDomain(params.orderNo);
			};
			xdr.onerror = function() {
				_result = false;
			};
			xdr.send();
		}
		else{
			jQuery.ajax({
				url        : 'novalnet_api.php',
				type   	   : 'post',
				dataType   : 'html',
				data       : { apiParams : params },
				global     :  false,
				async      :  false,
				success    :  function (result){
					if (currency == 'EUR')
						alert(result.replace('&euro;','€'));
					else
						alert(result.replace('&dollar;','$'));
					adminDisplayResponse(params.orderNo);
				}
			});
		}
	},0);
}

function isNumberKey(event) {
	var keycode = ( 'which' in event ) ? event.which : event.keyCode;
    var reg = /^(?:[0-9]+$)/;
    return ( reg.test( String.fromCharCode( keycode ) ) || keycode == 0 || keycode == 8 );
}

function adminDisplayResponse(order_no) {
	jQuery.ajax({
			url        : 'novalnet_transactions.php',
			type   	   : 'post',
			data       :  { orderNo : order_no },
			global     :  false,
			async      :  false,
			success    :  function (result){
				jQuery("#admin_order_display_block").html(result);
			}
	});
}

function adminDisplayResponseCrossDomain(order_no) {
	var xdr = new XDomainRequest(); // Use Microsoft XDR
	xdr.open('GET', 'novalnet_transactions.php?orderNo='+order_no);
	xdr.onload = function () {
		jQuery("#admin_order_display_block").html(this.responseText);
	};
	xdr.onerror = function() {
		_result = false;
	};
	xdr.send();
}
