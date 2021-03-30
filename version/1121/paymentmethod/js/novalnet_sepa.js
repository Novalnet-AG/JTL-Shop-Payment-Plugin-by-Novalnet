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
 * Novalnet Direct Debit SEPA Script
*/

jQuery(document).ready(function() {

    jQuery('#nn_payment_sepa').css('display','block');
    jQuery('#sepa_javascript_enable').css('display','none');

    var paymentFormId = jQuery('#nn_payment').closest('form').attr('id');

    if (jQuery('#one_click_shopping').val() && jQuery('#form_error').val() == '') {
        setSavedAccountProcess();
    } else {
        setNewAccountProcess();
    }

    jQuery('#nn_toggle_form').click(function() {
        if (jQuery('#nn_new_card_details').css('display') == 'block') {
            jQuery('#one_click_shopping').val('1');
            setSavedAccountProcess();
        } else {
            setNewAccountProcess();
        }
    });
    
    jQuery('#savepayment').click(function() {
        if (!jQuery('#savepayment').is(':checked')) {
            notSavePaymentData();
        } else {
            savePaymentData();
        }
	});
    
    jQuery('#'+paymentFormId).submit(function() {

        if (jQuery('#nn_new_card_details').css('display') == 'block') {

            if (jQuery('#nn_pin').length) {
                var nn_pin = jQuery.trim(jQuery('#nn_pin').val());

                if (nn_pin == '' && !(jQuery('#nn_forgot_pin').is(':checked'))) {
                    alert(jQuery('#nn_pin_empty_error_message').val());
                    return false;
                } else if (validateSpecialChars(nn_pin) && !(jQuery('#nn_forgot_pin').is(':checked'))) {
                    alert(jQuery('#nn_pin_error_message').val());
                    return false;
                }
            }
            
            if (jQuery('#nn_sepa_account_no').length) {
				
				var nn_sepa_account_no = jQuery('#nn_sepa_account_no').val();
				if(nn_sepa_account_no == '' || !isNaN(nn_sepa_account_no) || /^[a-zA-Z]+$/.test(nn_sepa_account_no))
				{
					alert( jQuery('#nn_lang_valid_account_details').val());
					return false;
				}else
				{
					jQuery('#nn_sepa_iban').val(nn_sepa_account_no);
				}
            }

            if (jQuery('#nn_dob').length && jQuery('#nn_guarantee_force').val() != '1') {
                var nn_dob = jQuery.trim(jQuery('#nn_dob').val());

                if (nn_dob == '') {
					alert(jQuery('#nn_dob_error_message').val());
					return false;
				} else if (/^([0-9]{2})\.([0-9]{2})\.([0-9]{4})$/.test(nn_dob) === false) {
					alert(jQuery('#nn_dob_valid_message').val());
					return false;
				}
            }

            if (jQuery('#nn_tel_number').length) {
                var nn_tel_number = jQuery.trim(jQuery('#nn_tel_number').val());

                if (nn_tel_number == '' || isNaN(nn_tel_number)) {
                    alert(jQuery('#nn_tele_error_message').val());
                    return false;
                }
            }

            if (jQuery('#nn_mob_number').length) {
                var nn_mob_number = jQuery.trim(jQuery('#nn_mob_number').val());

                if (nn_mob_number == '' || isNaN(nn_mob_number)) {
                    alert(jQuery('#nn_mob_error_message').val());
                    return false;
                }
            }

        }
    });

    
});


function validateSpecialChars(inputVal)
{
    var pattern = /^\s+|\s+$|([\/\\#,+@!^()$~%.":*?<>{}])/g;
    return pattern.test(inputVal);
}


function removeUnwantedSpecialChars(inputVal)
{
  return inputVal.replace(/^\s+|\s+$|([\/\\#,@+!^()$~%_":*?<>{}])/g,'');
}

function isAlphanumeric(event)
{
    var keycode = ('which' in event) ? event.which : event.keyCode;
    event = event || window.event;
    var reg = ((event.target || event.srcElement).id == 'nn_sepaowner') ? /[^0-9\[\]\/\\#,+@!^()$~%'"=:;<>{}\_\|*?`]/g: /^[a-z0-9]+$/i;
   
    return (reg.test(String.fromCharCode(keycode)) || keycode == 0 || keycode == 8);
}

function setSavedAccountProcess()
{
	jQuery('#nn_toggle_form').html(jQuery('#nn_account_display_text_new').val());
	jQuery('#one_click_shopping').val('1');
	jQuery('#is_fraudcheck').val('');
	jQuery('#nn_saved_details').show();
	jQuery('#nn_new_card_details').hide();
}

function setNewAccountProcess()
{
	jQuery('#nn_toggle_form').html(jQuery('#nn_account_display_text_saved').val());
	jQuery('#one_click_shopping').val('');
	jQuery('#nn_saved_details').hide();
	jQuery('#nn_new_card_details').show();
	jQuery('#is_fraudcheck').val(1);
}

function savePaymentData()
{
	jQuery('#nn_save_payment').val('1');
}

function notSavePaymentData()
{
	jQuery('#nn_save_payment').val('');
}

