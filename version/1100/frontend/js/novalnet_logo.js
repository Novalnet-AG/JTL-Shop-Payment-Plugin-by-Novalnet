/*
 * Novalnet Credit Card logos script
 * By Novalnet AG (http://www.novalnet.de)
 * Copyright (c) Novalnet AG
*/

if ( typeof(jQuery) == 'undefined' ) {
	var s  = document.createElement('script');
	s.type = 'text/javascript';
	s.src  = '/includes/plugins/novalnetag/version/1100/paymentmethod/js/novalnet.js';
	document.getElementsByTagName('head')[0].appendChild(s);
}

jQuery(document).ready(function() {
		
	var payment = jQuery( '#nn_payment' ).val();
	var nn_img_classname = jQuery( '#'+payment+' .radio label img' ).attr( 'class' );
	var payment_logo = ( jQuery( '#nn_logos' ).val() ).split('&');
	
	for ( var i=0,len=payment_logo.length;i<len;i++ ) {
		logo_src = payment_logo[i].split('=');
		var nn_img_element = '<img src="'+decodeURIComponent(logo_src[1])+'" class="'+nn_img_classname+'" alt="'+jQuery('#nn_logo_alt').val()+'">';		
		jQuery( '#'+payment+' .radio label p' ).before( nn_img_element );
	}	
});

