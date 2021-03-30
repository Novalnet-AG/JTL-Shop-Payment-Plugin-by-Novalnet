/*
 * Novalnet Direct Debit SEPA Script
 * By Novalnet (https://www.novalnet.de)
 * Copyright (c) Novalnet
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

    jQuery('#'+paymentFormId).submit(function() {

        if (jQuery('#nn_new_card_details').css('display') == 'block') {

            if (jQuery('#nn_pin').length) {
                nn_pin = jQuery.trim(jQuery('#nn_pin').val());

                if (nn_pin == '' && !(jQuery('#nn_forgot_pin').is(':checked'))) {
                    alert(jQuery('#nn_pin_empty_error_message').val());
                    return false;
                } else if (validateSpecialChars(nn_pin) && !(jQuery('#nn_forgot_pin').is(':checked'))) {
                    alert(jQuery('#nn_pin_error_message').val());
                    return false;
                }
            }

            if (jQuery('#nn_sepa_mandate_confirm').length && !(jQuery('#nn_sepa_mandate_confirm').is(':checked') )) {
                alert(jQuery('#nn_lang_mandate_confirm').val());
                return false;
            }

            if (jQuery('#nn_dob').length && jQuery('#nn_guarantee_force').val() != '1') {
                nn_dob = jQuery.trim(jQuery('#nn_dob').val());

                if (nn_dob == '') {
					alert(jQuery('#nn_dob_error_message').val());
					return false;
				} else if (/^([0-9]{2})\.([0-9]{2})\.([0-9]{4})$/.test(nn_dob) === false) {
					alert(jQuery('#nn_dob_valid_message').val());
					return false;
				}
            }

            if (jQuery('#nn_tel_number').length) {
                nn_tel_number = jQuery.trim(jQuery('#nn_tel_number').val());

                if (nn_tel_number == '' || isNaN(nn_tel_number)) {
                    alert(jQuery('#nn_tele_error_message').val());
                    return false;
                }
            }

            if (jQuery('#nn_mob_number').length) {
                nn_mob_number = jQuery.trim(jQuery('#nn_mob_number').val());

                if (nn_mob_number == '' || isNaN(nn_mob_number)) {
                    alert(jQuery('#nn_mob_error_message').val());
                    return false;
                }
            }

        }
    });

    jQuery('#nn_sepa_mandate_confirm').click(function() {
        if (!jQuery('#nn_sepa_mandate_confirm').is(':checked')) {
            sepaMandateUnconfirmProcess();
        } else {
            ibanBicCall();
        }
    });

    jQuery('#nn_sepaowner, #nn_sepa_account_no, #nn_sepa_country, #nn_sepa_bank_code').change(function() {
        sepaMandateUnconfirmProcess();
    });
});

function sepaMandateUnconfirmProcess()
{
    jQuery('#nn_sepa_mandate_confirm').attr('checked', false);
    jQuery('#nn_payment_hash').val('');
    jQuery('#novalnet_sepa_iban_span, #novalnet_sepa_bic_span').html('');
}

function ibanBicCall()
{
    bank_country = jQuery('#nn_sepa_country').val();
    account_holder = removeUnwantedSpecialChars(jQuery('#nn_sepaowner').val());
    account_no = jQuery('#nn_sepa_account_no').val();
    bank_code = jQuery('#nn_sepa_bank_code').val();

    jQuery('#nn_sepa_iban, #nn_sepa_bic').val('');

    if (isNaN(account_no) && isNaN(bank_code)) {
        jQuery('#novalnet_sepa_iban_span, #novalnet_sepa_bic_span').html('');
        sepaHashCall();
        return false;
    }

    if (bank_code == '' && isNaN(account_no)) {
        sepaHashCall();
        return false;
    }

    if (bank_country == '' || account_holder == '' || account_no == '' || bank_code == '' || isNaN(bank_code) || isNaN(account_no)) {
        alert(jQuery('#nn_lang_valid_account_details').val());
        sepaMandateUnconfirmProcess();
        return false;
    }

    jQuery('#nn_loader').css('display','block');

    var ibanBicRequestParams = {'account_holder' : account_holder , 'bank_account' : account_no , 'bank_code' : bank_code, 'vendor_id' : jQuery('#novalnet_vendor').val(), 'vendor_authcode' : jQuery('#novalnet_authcode').val(), 'bank_country' : bank_country, 'unique_id' : jQuery('#nn_sepaunique_id').val(), 'remote_ip' : jQuery('#nn_remote_ip').val(), 'get_iban_bic' : 1};

    sepaRequestHandler(ibanBicRequestParams, 'ibanbic');
}


function sepaHashCall()
{
    var account_no = '';var bank_code = '';

    bank_country = jQuery('#nn_sepa_country').val();
    account_holder = removeUnwantedSpecialChars(jQuery('#nn_sepaowner').val());
    iban = jQuery('#nn_sepa_account_no').val().replace(/[^a-z0-9]+/gi, '');
    bic = jQuery('#nn_sepa_bank_code').val().replace(/[^a-z0-9]+/gi, '');
    nn_sepa_iban = jQuery('#nn_sepa_iban').val();
    nn_sepa_bic = jQuery('#nn_sepa_bic').val();

    if (bank_country == '' || account_holder == '' || iban == '') {
        alert(jQuery('#nn_lang_valid_account_details').val()) ;
        sepaMandateUnconfirmProcess();
        return false;
    }

    if (bic == '') {
        if (bank_country == 'DE' && isNaN(iban)) {
            bic = '123456';
        } else {
            alert(jQuery('#nn_lang_valid_account_details').val());
            sepaMandateUnconfirmProcess();
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

    jQuery('#nn_loader').css('display','block');

    var sepaHashRequestParams = {'account_holder' : account_holder, 'bank_account' : account_no, 'bank_code' : bank_code, 'vendor_id' : jQuery('#novalnet_vendor').val(), 'vendor_authcode' : jQuery('#novalnet_authcode').val(), 'bank_country' : bank_country, 'unique_id' : jQuery('#nn_sepaunique_id').val(), 'sepa_data_approved' : 1, 'mandate_data_req' : 1, 'iban' : iban, 'bic' : bic, 'remote_ip' : jQuery('#nn_remote_ip').val()};

    sepaRequestHandler(sepaHashRequestParams, 'hash');
}

function sepaRequestHandler(sepaRequestParams, type)
{
    // Cross domain request for IE8 & 9 only
    if ('XDomainRequest' in window && window.XDomainRequest !== null) {
        var xdr = new XDomainRequest(); // Use Microsoft XDR
        sepaRequestParams = jQuery.param( sepaRequestParams );
        xdr.open('POST','https://payport.novalnet.de/sepa_iban');
        xdr.onload = function (){
            // XDomainRequest doesn't provide responseXml, so if you need it:
            sepaResponseHandler(jQuery.parseJSON(this.responseText), type);
        };
        xdr.onerror = function() {
            _result = false;
        };
        xdr.send(sepaRequestParams);
    } else {
        jQuery.ajax({
            type: 'POST',
            url : 'https://payport.novalnet.de/sepa_iban',
            data: sepaRequestParams,
            dataType: 'json',
            success: function(data) {
                sepaResponseHandler(data, type);
            }
        });
    }
}

function sepaResponseHandler(sepaCallResponseParams, type)
{
    if (sepaCallResponseParams.hash_result == 'success') {

        switch (type) {

            case 'hash':
                jQuery('#nn_payment_hash').val(sepaCallResponseParams.sepa_hash);
                jQuery('#nn_loader').css('display', 'none');
                break;

            case 'ibanbic':

                if (sepaCallResponseParams.IBAN != '') {
					jQuery('#nn_sepa_iban').val(sepaCallResponseParams.IBAN);
                    jQuery('#novalnet_sepa_iban_span').html('<b>IBAN:</b> '+ sepaCallResponseParams.IBAN);
                }

                if (sepaCallResponseParams.BIC != '') {
					jQuery('#nn_sepa_bic').val(sepaCallResponseParams.BIC);
                    jQuery('#novalnet_sepa_bic_span').html('<b>BIC:</b> '+ sepaCallResponseParams.BIC);
                } else {
                    jQuery('#nn_loader').css('display','none');
                    alert( jQuery('#nn_lang_valid_account_details').val());
                    sepaMandateUnconfirmProcess();
                    return false;
                }

                sepaHashCall();
                return true;
                break;

            case 'refill':
                jQuery('#nn_loader').css('display','none');
                var hash_string = sepaCallResponseParams.hash_string.split('&');
                var arrayResult={};
                for (var i=0,len=hash_string.length;i<len;i++) {
                    if(hash_string[i]=='' || hash_string[i].indexOf("=") == -1) {
                        hash_string[i] = hash_string[i-1] +'&'+hash_string[i];
                    }
                    var hash_result_val = hash_string[i].split('=');
                    arrayResult[hash_result_val[0]] = hash_result_val[1];
                }
                try{
                    var holder = decodeURIComponent(escape(arrayResult.account_holder));
                }catch(e) {
                    var holder = arrayResult.account_holder;
                }
                jQuery('#nn_sepaowner').val(holder);
                jQuery('#nn_sepa_country').val(arrayResult.bank_country);
                jQuery('#nn_sepa_country').change();
                jQuery('#nn_sepa_account_no').val(arrayResult.iban);

                if (arrayResult.bic != '123456')
                    jQuery('#nn_sepa_bank_code').val(arrayResult.bic);
                break;
        }
    } else {
        alert(sepaCallResponseParams.hash_result);
        sepaMandateUnconfirmProcess();
        jQuery('#nn_loader').css('display','none');
        return false;
    }
}

function sepaRefillFormcall()
{
    var refillpanhash = '';

    if (jQuery('#nn_sepa_input_panhash').length) {
        refillpanhash = jQuery('#nn_sepa_input_panhash').val();
    }

    if (refillpanhash == '') {
        return false;
    }

    jQuery('#nn_loader').css('display','block');

    var refillRequestParams = {'vendor_id' : jQuery('#novalnet_vendor').val() , 'vendor_authcode' : jQuery('#novalnet_authcode').val() , 'unique_id' : jQuery('#nn_sepaunique_id').val(), 'sepa_data_approved' : 1, 'mandate_data_req' : 1, 'sepa_hash' : refillpanhash, 'remote_ip' : jQuery('#nn_remote_ip').val()};

    sepaRequestHandler(refillRequestParams, 'refill');
}

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

sepaRefillFormcall();
