/*
 * Novalnet Credit Card Script
 * By Novalnet AG (http://www.novalnet.de)
 * Copyright (c) Novalnet AG
*/
if ( typeof(jQuery) == 'undefined' ) {
	var s  = document.createElement("script");
	s.src  = '/includes/plugins/novalnetag/version/1100/paymentmethod/js/novalnet.js';
	s.type = 'text/javascript';
	document.getElementsByTagName('head')[0].appendChild(s);
}

jQuery(document).ready(function() {
	
	jQuery('#nn_payment_cc').css('display','block');
	jQuery('#cc_javascript_enable').css('display','none');
	
	var paymentFormId = jQuery('#nn_payment').closest('form').attr('id');

	if ( jQuery('#one_click_shopping').val() && jQuery('#form_error').val() == '' ) {
		jQuery('#nn_toggle_form').html(jQuery('#nn_cc_display_text_new').val());
		jQuery('#nn_saved_details').show();
		jQuery('#nn_new_card_details').hide();
		jQuery('#nn_creditcard_saved_desc').show();
		jQuery('#nn_creditcard_redirect_desc').hide();
	} else {
		jQuery('#nn_toggle_form').html(jQuery('#nn_cc_display_text_saved').val());
		jQuery('#one_click_shopping').val('');
		jQuery('#nn_saved_details').hide();
		jQuery('#nn_new_card_details').show();
		jQuery('#nn_creditcard_saved_desc').hide();
		jQuery('#nn_creditcard_redirect_desc').show();
	}

	jQuery('#nn_toggle_form').click(function() {
		if (jQuery('#nn_new_card_details').css('display') == 'block') {
			jQuery('#nn_toggle_form').html(jQuery('#nn_cc_display_text_new').val());
			jQuery('#one_click_shopping').val(1);
			jQuery('#nn_saved_details').show();
			jQuery('#nn_new_card_details').hide();
			jQuery('#nn_creditcard_saved_desc').show();
			jQuery('#nn_creditcard_redirect_desc').hide();
		} else {
			jQuery('#nn_toggle_form').html(jQuery('#nn_cc_display_text_saved').val());
			jQuery('#one_click_shopping').val('');
			jQuery('#nn_saved_details').hide();
			jQuery('#nn_new_card_details').show();
			jQuery('#nn_creditcard_saved_desc').hide();
			jQuery('#nn_creditcard_redirect_desc').show();
		}
	});
	
	jQuery('#'+ paymentFormId).submit(function (evt) {
		
		if ( jQuery('#nn_saved_details').css('display') == 'block' && jQuery('#nn_cvvnumber').length && jQuery('#nn_cvvnumber').val() == '' ) {
			alert( jQuery('#nn_cc_valid_error_ccmessage').val() );
			return false;
		}
	});
});

function showCvcInfo( cvcInfoDisplay )
{	
	if ( cvcInfoDisplay ) {
		jQuery('#cvc_info').css('display','block');
		var position = jQuery('#showcvc').position();		
		jQuery('#cvc_info').css('position','absolute');
		jQuery('#cvc_info').css('top',(position.top-85));
		jQuery('#cvc_info').css('left',(position.left+25));
		jQuery('#cvc_info').css('z-index','999');
	} else {
		jQuery('#cvc_info').css('display','none');
	}
}

function isNumberKey( event )
{
	var keycode = ( 'which' in event ) ? event.which : event.keyCode;
	event = event || window.event;
    return ( /^[0-9]+$/i.test( String.fromCharCode( keycode ) ) || keycode == 0 || keycode == 8 || (event.ctrlKey == true && keycode == 114 ) );
}
