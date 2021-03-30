/*
 * Novalnet PayPal Script
 * By Novalnet (https://www.novalnet.de)
 * Copyright (c) Novalnet
*/

jQuery(document).ready(function() {

    var paymentFormId = jQuery('#nn_payment').closest('form').attr('id');

    if (jQuery('#one_click_shopping').val() && jQuery('#form_error').val() == '') {
        setSavedDetailsProcess();
    } else {
        setNewDetailsProcess();
    }

    jQuery('#nn_toggle_form').click(function() {
        if (jQuery('#nn_new_paypal_details').css('display') == 'block') {
			jQuery('#one_click_shopping').val('1');
			setSavedDetailsProcess();
        } else {
            setNewDetailsProcess();
        }
    });
});

function setSavedDetailsProcess()
{
	jQuery('#nn_toggle_form').html(jQuery('#nn_account_display_text_new').val());
	jQuery('#one_click_shopping').val('1');
	jQuery('#nn_saved_paypal_details, #nn_paypal_saved_desc').show();
	jQuery('#nn_paypal_redirect_desc, #nn_new_paypal_details').hide();
}

function setNewDetailsProcess()
{
	jQuery('#nn_toggle_form').html(jQuery('#nn_account_display_text_saved').val());
	jQuery('#one_click_shopping').val('');
	jQuery('#nn_saved_paypal_details, #nn_paypal_saved_desc').hide();
	jQuery('#nn_new_paypal_details, #nn_paypal_redirect_desc').show();
}
