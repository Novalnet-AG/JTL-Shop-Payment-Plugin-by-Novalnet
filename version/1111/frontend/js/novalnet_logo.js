/*
 * Novalnet Credit Card logos script
 * By Novalnet AG (https://www.novalnet.de)
 * Copyright (c) Novalnet AG
*/

jQuery(document).ready(function() {

    var payment = jQuery('#nn_payment').val();
    var payment_logo = (jQuery('#nn_logos').val()).split('&');

    if (jQuery('#nn_mobile_version').val() == true) {
		var payment_no = jQuery('#nn_payment_no').val();
		var nn_img_classname = jQuery('#payment'+payment_no).parent().find('label img').attr('class');
	} else {
		var shop_element = jQuery('#nn_shop_element').val();
		var nn_img_classname = jQuery('#'+payment+' '+shop_element+' label img').attr('class');
	}

    for (var i=0,len=payment_logo.length;i<len;i++) {
        logo_src = payment_logo[i].split('=');
        var nn_img_element = '<img src="'+decodeURIComponent(logo_src[1])+'" class="'+nn_img_classname+'" alt="'+jQuery('#nn_logo_alt').val()+'" hspace="1">';
        if (jQuery('#nn_mobile_version').val() == true) {
			jQuery('#payment'+payment_no).parent().find('label p').before(nn_img_element);
		} else {
			jQuery('#'+payment+' '+shop_element+' label p').before(nn_img_element);
		}
    }
});
