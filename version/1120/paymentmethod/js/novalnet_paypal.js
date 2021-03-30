/*
 * Novalnet payment plugin
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Novalnet End User License Agreement
 *
 * DISCLAIMER
 *
 * If you wish to customize Novalnet payment extension for your needs,
 * please contact technic@novalnet.de for more information.
 *
 * @author  	Novalnet AG
 * @copyright  	Copyright (c) Novalnet
 * @license    	https://www.novalnet.de/payment-plugins/kostenlos/lizenz
 *
 * Novalnet PayPal Script
*/

jQuery(document).ready(function () {

    if (jQuery('#one_click_shopping').val() && jQuery('#form_error').val() == '') {
        setSavedDetailsProcess();
    } else {
        setNewDetailsProcess();
    }

    jQuery('#nn_toggle_form').click(function () {
        if (jQuery('#nn_new_paypal_details').css('display') == 'block') {
            jQuery('#one_click_shopping').val('1');
            setSavedDetailsProcess();
        } else {
            setNewDetailsProcess();
        }
    });
    
    jQuery('#savepayment').click(function() {
        if (!jQuery('#savepayment').is(':checked')) {
            notSavePaymentData();
        } else {
            savePaymentData();
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

function savePaymentData()
{
	jQuery('#nn_save_payment').val('1');
}

function notSavePaymentData()
{
	jQuery('#nn_save_payment').val('');
}


