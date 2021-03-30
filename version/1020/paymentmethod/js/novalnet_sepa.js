/*
 * Novalnet Direct Debit SEPA Script
 * By Novalnet AG (http://www.novalnet.de)
 * Copyright (c) Novalnet
*/
if(typeof(jQuery) == 'undefined') {
    var s = document.createElement("script");
    s.type = "text/javascript";
    s.src = "/includes/plugins/novalnetag/version/1020/paymentmethod/js/jquery.js";
    document.getElementsByTagName("head")[0].appendChild(s);
}

jQuery(document).ready(function() {

    jQuery('#nn_payment_sepa').css('display','block');
    jQuery('#sepa_javascript_enable').css('display','none');

    var formid = jQuery('#nn_payment').closest('form').attr('id');

    jQuery('#'+formid).submit(function (evt) {

        if ( jQuery('#nn_pin').length ) {
                nn_pin = jQuery.trim( jQuery('#nn_pin').val() );

            if(nn_pin == '' && !(jQuery('#nn_forgot_pin').is(':checked'))){
                alert(jQuery('#nn_pin_empty_error_message').val());
                return false;
            }
            else if(validateSpecialChars(nn_pin) && !(jQuery('#nn_forgot_pin').is(':checked'))){
                alert(jQuery('#nn_pin_error_message').val());
                jQuery('#nn_pin').val('');
                return false;
            }
        }
        if ( jQuery('#nn_sepa_mandate_confirm').length && !( jQuery('#nn_sepa_mandate_confirm').is(':checked') ) ) {
            alert(jQuery('#nn_lang_mandate_confirm').val());
            return false;
        }

        if ( jQuery('#nn_telnumber').length ) {
                nn_tel_number = jQuery.trim( jQuery('#nn_telnumber').val() );

                if ( nn_tel_number == '' || isNaN( nn_tel_number ) ) {
                    alert( jQuery('#nn_tele_error_message').val() );
                    return false;
                }
            }

        if ( jQuery('#nn_mob_number').length ) {
                nn_mob_number = jQuery.trim( jQuery('#nn_mob_number').val() );

                if ( nn_mob_number == '' || isNaN( nn_mob_number ) ) {
                    alert( jQuery('#nn_mob_error_message').val() );
                    return false;
                }
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
    jQuery('#nn_sepapanhash').val('');
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
  return input_val.replace(/^\s+|\s+$|([\/\\#,@+!^()$~%_":*?<>{}])/g,'');
}

function isAlphanumeric(event)
{
    var keycode = ( 'which' in event ) ? event.which : event.keyCode;
    event = event || window.event;
    var reg = ( ( event.target || event.srcElement ).id == 'nn_sepaowner' ) ? /^[a-z-&\s.]+$/i : /^[a-z0-9]+$/i;
    return ( reg.test( String.fromCharCode( keycode ) ) || keycode == 0 || keycode == 8 );
}

function sepahashrequestcall()
{
    var bank_country = "";var account_holder = "";var account_no = "";
    var iban = "";var bic = "";var bank_code = "";var nn_sepa_uniqueid = "";
    var nn_vendor = "";var nn_auth_code = "";var mandate_confirm = 0;

    bank_country = jQuery('#nn_sepa_country').length ? jQuery('#nn_sepa_country').val() : '';
    account_holder = jQuery('#nn_sepaowner').length ? removeUnwantedSpecialChars( jQuery('#nn_sepaowner').val() ) : '';
    iban = jQuery('#nn_sepa_account_no').length ? jQuery('#nn_sepa_account_no').val() : '';
    bic = jQuery('#nn_sepa_bank_code').length ? jQuery('#nn_sepa_bank_code').val() : '';
    nn_sepa_iban = jQuery('#nn_sepa_iban').length ? jQuery('#nn_sepa_iban').val() : '';
    nn_sepa_bic = jQuery('#nn_sepa_bic').length ? jQuery('#nn_sepa_bic').val() : '';
    nn_vendor = jQuery('#nn_vendor').length ? jQuery('#nn_vendor').val() : '';
    nn_auth_code = jQuery('#nn_authcode').length ? jQuery('#nn_authcode').val() : '';
    nn_sepa_uniqueid = jQuery('#nn_sepaunique_id').length ? jQuery('#nn_sepaunique_id').val() : '';

    if(nn_vendor == '' || nn_auth_code == '') {alert(jQuery('#nn_lang_valid_merchant_credentials').val()); sepa_mandate_unconfirm_process(); return false;}

    if(account_holder == '' || iban == '' || nn_vendor == '' || nn_auth_code == '' || nn_sepa_uniqueid == '') {
        alert(jQuery('#nn_lang_valid_account_details').val());sepa_mandate_unconfirm_process(); return false;
    }

    if(bank_country == '' || bank_country == null) {
        alert(jQuery('#nn_lang_valid_country_details').val());sepa_mandate_unconfirm_process(); return false;
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
    jQuery('#nn_loader').css('display','block');

    var nnurl_val = {'account_holder' : account_holder , 'bank_account' : account_no , 'bank_code' : bank_code, 'vendor_id' : nn_vendor, 'vendor_authcode' : nn_auth_code, 'bank_country' : bank_country, 'unique_id' : nn_sepa_uniqueid, 'sepa_data_approved' : 1, 'mandate_data_req' : 1 , 'iban' : iban , 'bic' : bic };

    // IE8 & 9 only Cross domain JSON GET request
    if ('XDomainRequest' in window && window.XDomainRequest !== null) {
        var xdr = new XDomainRequest(); // Use Microsoft XDR
        nnurl_val = jQuery.param(nnurl_val);
        xdr.open('POST',"https://payport.novalnet.de/sepa_iban");
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
            url : "https://payport.novalnet.de/sepa_iban",
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
        jQuery('#nn_sepapanhash').val( data.sepa_hash );
        jQuery('#nn_sepapanhash').attr( 'disabled', false );
        jQuery('#nn_loader').css('display','none');
    }else{
        alert(dataobj.hash_result);
        jQuery('#nn_loader').css('display','none');
        return false;
    }
}
function sepaibanbiccall()
{
    var bank_country = "";var account_holder = "";var account_no = "";
    var bank_code = "";var nn_sepa_uniqueid = "";
    var nn_vendor = "";var nn_auth_code = "";

    bank_country = jQuery('#nn_sepa_country').length ? jQuery('#nn_sepa_country').val() : '';
    account_holder = jQuery('#nn_sepaowner').length ? removeUnwantedSpecialChars( jQuery('#nn_sepaowner').val() ) : '';
    account_no = jQuery('#nn_sepa_account_no').length ? jQuery('#nn_sepa_account_no').val() : '';
    bank_code = jQuery('#nn_sepa_bank_code').length ? jQuery('#nn_sepa_bank_code').val() : '';
    nn_vendor = jQuery('#nn_vendor').length ? jQuery('#nn_vendor').val() : '';
    nn_auth_code = jQuery('#nn_authcode').length ? jQuery('#nn_authcode').val() : '';
    nn_sepa_uniqueid = jQuery('#nn_sepaunique_id').length ? jQuery('#nn_sepaunique_id').val() : '';
    jQuery('#nn_sepa_iban').val('');
    jQuery('#nn_sepa_bic').val('');

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
 
    if (account_holder == '' || account_no == '' || bank_code == '' || nn_vendor == '' || nn_auth_code == '' || nn_sepa_uniqueid == '' || isNaN(bank_code) || isNaN(account_no)) {
        alert(jQuery('#nn_lang_valid_account_details').val());
        sepa_mandate_unconfirm_process();
        return false;
    }

    if(bank_country == '' || bank_country == null) {
        alert(jQuery('#nn_lang_valid_country_details').val());
        sepa_mandate_unconfirm_process();
        return false;
    }
    jQuery('#nn_loader').css('display','block');

    var nnurl_val = {'account_holder' : account_holder , 'bank_account' : account_no , 'bank_code' : bank_code, 'vendor_id' : nn_vendor, 'vendor_authcode' : nn_auth_code, 'bank_country' : bank_country, 'unique_id' : nn_sepa_uniqueid, 'get_iban_bic' : 1};

    // IE8 & 9 only Cross domain JSON GET request
    if ('XDomainRequest' in window && window.XDomainRequest !== null) {
        var xdr = new XDomainRequest(); // Use Microsoft XDR
        nnurl_val = jQuery.param(nnurl_val);
        xdr.open('POST', "https://payport.novalnet.de/sepa_iban");
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
            url : "https://payport.novalnet.de/sepa_iban",
            data: nnurl_val,
            dataType: 'json',
            success: function(data) {
                getSepaHashResult(data);
            }
        });
    }
}

function getSepaHashResult(data)
{
    if(data.hash_result == 'success')
    {
        jQuery('#nn_sepa_iban').val( data.IBAN );
        jQuery('#nn_sepa_bic').val( data.BIC );
        if(data.IBAN != '')
        {
            jQuery('#novalnet_sepa_iban_span').html('<b>IBAN:</b> '+data.IBAN);
        }

        if(data.BIC != '')
        {
            jQuery('#novalnet_sepa_bic_span').html('<b>BIC:</b> '+data.BIC);
        }
        else{
            jQuery('#nn_loader').css('display','none');
            alert(jQuery('#nn_lang_valid_account_details').val());
            sepa_mandate_unconfirm_process();
            return false;
        }
    }else{
        alert(dataobj.hash_result);
        jQuery('#nn_loader').css('display','none');
        return false;
    }
    sepahashrequestcall();
    return true;
}
// AJAX call for refill sepa form elements
function separefillformcall()
{
    var refillpanhash = '';
    refillpanhash = jQuery('#nn_sepa_input_panhash').length ? jQuery('#nn_sepa_input_panhash').val() : '';

    if (refillpanhash == '' || typeof refillpanhash == 'undefined') {
        return false;
    }
    var nn_vendor = "";
    var nn_auth_code = "";
    var nn_uniqueid = "";

    nn_vendor = jQuery('#nn_vendor').length ? jQuery('#nn_vendor').val() : '';
    nn_authcode = jQuery('#nn_authcode').length ? jQuery('#nn_authcode').val() : '';
    nn_sepaunique_id = jQuery('#nn_sepaunique_id').length ? jQuery('#nn_sepaunique_id').val() : '';

    if(nn_vendor == '' || nn_authcode == '' || nn_sepaunique_id == '') {return false;}
    jQuery('#nn_loader').css('display','block');
    var nnurl_val = "vendor_id="+nn_vendor+"&vendor_authcode="+nn_authcode+"&unique_id="+nn_sepaunique_id+"&sepa_data_approved=1&mandate_data_req=1&sepa_hash="+refillpanhash;

        if ('XDomainRequest' in window && window.XDomainRequest !== null) {
            var xdr = new XDomainRequest(); // Use Microsoft XDR
            xdr.open('POST',"https://payport.novalnet.de/sepa_iban");
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
                url : "https://payport.novalnet.de/sepa_iban",
                data: nnurl_val,
                dataType: 'json',
                success: function(data) {
                    refillResponse(data);
                },
                error : function(data) {
                    jQuery('#nn_loader').css('display','none');
                }
            });
        }
}

function refillResponse(data)
{
    jQuery('#nn_loader').css('display','none');
    if (data.hash_result == "success")
    {
        jQuery('#nn_loader').css('display','none');
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
