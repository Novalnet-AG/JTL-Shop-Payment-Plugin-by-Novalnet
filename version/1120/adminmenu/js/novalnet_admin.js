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
 * Novalnet admin management script
*/

/**************** Novalnet Javascript file for handling admin operations *****/

jQuery(document).ready( function() {

    jQuery('input[name=callback_notify_url]').val(jQuery('#nn_callback_url').val());

    jQuery.each([ 'vendorid', 'authcode', 'productid', 'key_password'], function(i, key) {
        jQuery('input[name='+key+']').attr('readonly', true);
    });

    if (jQuery('#tariffid').val() == undefined) {
        jQuery('input[name=tariffid]').attr('id', 'tariffid');
    }

    if (jQuery('input[name=novalnet_public_key]').val() != undefined && jQuery('input[name=novalnet_public_key]').val() != '') {
        jQuery('#nn_loader').css('display','block');
        fillMerchantConfiguration();
    } else if (jQuery('input[name=novalnet_public_key]').val() == '') {
        emptyMandatoryValues();
    }

    jQuery('input[name=novalnet_public_key]').on('change', function() {
        if (jQuery('input[name=novalnet_public_key]').val() != '') {
            jQuery('#nn_loader').css('display','block');
            fillMerchantConfiguration();
        } else if (jQuery('input[name=novalnet_public_key]').val() == '') {
            emptyMandatoryValues();
        }
    });

    jQuery('#transaction_details').addClass('nn_active');

    jQuery('.nn_accordion_section_title').click(function(event) {

        var currentAttrValue = jQuery(this).attr('href');

        if (jQuery(event.target).is('.nn_active')) {
            closeAccordionSection();
        } else {
            closeAccordionSection();
            jQuery(this).addClass('nn_active');
            jQuery('.nn_accordion ' + currentAttrValue).slideDown(200);
        }

        event.preventDefault();
    });

});

function closeAccordionSection() {
    jQuery('.nn_accordion .nn_accordion_section_title').removeClass('nn_active');
    jQuery('.nn_accordion .nn_accordion_section_content').slideUp(200);
}

function isNumberKey(event) {
    var keycode = ('which' in event) ? event.which : event.keyCode;
    var reg = /^(?:[0-9]+$)/;
    return (reg.test(String.fromCharCode(keycode)) || keycode == 0 || keycode == 8);
}

function adminOrderDisplay(orderNumber)
{
    jQuery('#nn_loader').css('display','block');

    jQuery('.adminCover').css({
        display : 'block',
        width   : jQuery(document).width(),
        height  : jQuery(document).height()
    });

    jQuery('.adminCover').css({opacity:0}).animate({opacity:0.5, backgroundColor:'#878787'});

    setTimeout(function() {
        if (jQuery(window).width() < 850) {
            jQuery('#admin_order_display_block').css({left:(jQuery(window).width()/2),top:(jQuery(window).height()/2),width:0,height:0}).animate( {left:(( jQuery(window).width() - (jQuery(window).width() - 10) )/2),top:5,width:(jQuery(window).width() - 10),height:(jQuery(window).height()-10)} );
            jQuery('#overlay_window_block_body').css({'height':(jQuery(window).height()-95)});
        } else {
            jQuery('#admin_order_display_block').css({left:((jQuery(window).height())*0.40),top:((jQuery(window).height())*0.07),width:((jQuery(window).height())*1.50),height:'90%'} );
        }

        var overviewOrderParams = {'orderNo' : orderNumber , 'pluginInc' : jQuery('#nn_plugin_include').val()};
        transactionRequestHandler(overviewOrderParams, jQuery( '#nn_admin_url' ).val() + 'transactions.php?', jQuery( '#nn_admin_url' ).val() + 'transactions.php', 'orders');
    }, 200);
}

function overviewCloseButton()
{
    jQuery('#admin_order_display_block').hide();
    jQuery('.adminCover').css('display','none');
    return true;
}

function extensionRequestProcess(params)
{
    jQuery('.confirm').attr({'style':'color: #000000 !important; background-color: #878787 !important;','disabled':'disabled'});
    extensionRequestParams = jQuery.extend(params, {'pluginInc': jQuery('#nn_plugin_include').val()});
    transactionRequestHandler(extensionRequestParams, jQuery( '#nn_admin_url' ).val() + 'inc/Novalnet.extension.php?', jQuery( '#nn_admin_url' ).val() + 'inc/Novalnet.extension.php', 'api');
}

function captureval(api_orderno, capture_code)
{
    var transactionRequestParams = {'orderNo' : api_orderno , 'apiStatus' : capture_code};

    if (jQuery('#book_amount').length) {
        var book_value = jQuery('#book_amount').val();

        if (book_value == ''  || book_value == 0) {
            alert('Ungültiger Betrag');
            return false;
        }

        if (!confirm('Sind Sie sich sicher, dass Sie den Bestellbetrag buchen wollen?')) {
            return false;
        }

        transactionRequestParams = Object.assign(transactionRequestParams, {'bookAmount' : book_value});
    } else {
        var nn_confirm_text = (capture_code == 'capture' ? 'Sind Sie sicher, dass Sie die Zahlung einziehen möchten?' : 'Sind Sie sicher, dass Sie die Zahlung stornieren wollen?');

        if (!confirm(nn_confirm_text)) {
            return false;
        }
    }
    jQuery('#nn_loader_extension').css('display','block');
    extensionRequestProcess(transactionRequestParams);
}

function refundval(api_orderno, refund_code)
{
    var amount_refund = jQuery.trim(jQuery('#amount_refund_val').val());
    var refund_ref    = jQuery.trim(jQuery('#refund_ref').val());

    if (amount_refund == '' || amount_refund <= 0) {
        alert('Geben Sie bitte den korrekten Betrag für die Rückerstattung ein');
        return false;
    }

    if (!confirm('Sind Sie sicher, dass Sie den Betrag zurückerstatten möchten?')) {
        return false;
    }

    var refundRequestParams = { 'orderNo' : api_orderno, 'apiStatus' : refund_code,  'refundAmount' : amount_refund, 'refundRef' : refund_ref };
    jQuery('#nn_loader_extension').css('display','block');
    extensionRequestProcess(refundRequestParams);
}

function amountupdate(api_orderno, payment_method, amount_update)
{
    var amount_value = jQuery('#amount_update_val').val();

    if (amount_value == '' || amount_value == 0) {
        alert('Ungültiger Betrag');
        return false;
    }
    var duedate_value = '';
    if (jQuery('#duedate_update_val_days').length) {
        var duedate_day_value   = jQuery('#duedate_update_val_days').val();
        var duedate_month_value = jQuery('#duedate_update_val_month').val();
        var duedate_year_value  = jQuery('#duedate_update_val_year').val();
        duedate_value       = duedate_year_value + '-' + duedate_month_value + '-' + duedate_day_value;
        var date = new Date();
        var current_day_value   = ('0' + date.getDate()).slice(-2);
        var current_month_value = ('0' + (date.getMonth() + 1)).slice(-2);
        var current_year_value  = date.getFullYear();
        var current_date_value  = current_year_value + '-' + current_month_value + '-' + current_day_value;
        var due_date_formatted  = duedate_day_value + '.' + duedate_month_value + '.' + duedate_year_value;

        if (!isValidDate(duedate_value)) {
            alert('Ungültiges Fälligkeitsdatum');
            return false;
        }

        if (duedate_value < current_date_value) {
            alert('Das Datum sollte in der Zukunft liegen');
            return false;
        }

        if (payment_method == 'novalnet_cashpayment') {
            if (!confirm('Sind Sie sicher, dass sie den Bestellbetrag / das Ablaufdatum des Zahlscheins ändern wollen?')) {
                return false;
            }
        } else {
            if (!confirm('Sind Sie sich sicher, dass Sie den Betrag / das Fälligkeitsdatum der Bestellung ändern wollen?')) {
                return false;
            }
        }
    }
    else if (!confirm('Sind Sie sich sicher, dass Sie den Bestellbetrag ändern wollen?')) {
        return false;
    }

    var amountupdateRequestParams = {'orderNo' : api_orderno, 'apiStatus' : amount_update, 'amount' : amount_value ,'dueDateChange' : duedate_value};
    jQuery('#nn_loader_extension').css('display','block');
    extensionRequestProcess(amountupdateRequestParams);
}

function fillMerchantConfiguration()
{
     var autoconfigurationRequestParams = {'api_config_hash' : jQuery('input[name=novalnet_public_key]').val(),'pluginInc' : jQuery('#nn_plugin_inc').val()};
     
    transactionRequestHandler(autoconfigurationRequestParams, jQuery( '#nn_admin_url' ).val() + 'autoconfiguration.php?', jQuery( '#nn_admin_url' ).val() + 'autoconfiguration.php', 'autofill');
}

function transactionRequestHandler(requestParams, get_url, post_url, type)
{

    requestParams = typeof(requestParams !== 'undefined') ? requestParams : '';
    setTimeout(function() {
        if ('XDomainRequest' in window && window.XDomainRequest !== null) {
            var xdr = new XDomainRequest();
            var query = jQuery.param(requestParams);
            xdr.open('GET', get_url + query) ;
            xdr.onload = function () {
                transactionResponseHandler(type, this.responseText, requestParams);
            };
            xdr.onerror = function() {
                _result = false;
            };
            xdr.send();
        }
        else {
            jQuery.ajax({
                url        :  post_url,
                type       : 'post',
                dataType   : 'html',
                data       :  requestParams,
                global     :  false,
                async      :  false,
                success    :  function (result){
                    transactionResponseHandler(type, result, requestParams);
                }
            });
        }
    }, 0);
}

function transactionResponseHandler(type, result, transactionResponseParams)
{
    
    transactionResponseParams = typeof(transactionResponseParams !== 'undefined') ? transactionResponseParams : '';

    switch(type) {

        case 'api':
            alert(result);
            
            var extensionResponseParams = {'orderNo' : transactionResponseParams.orderNo , 'pluginInc' : jQuery('#nn_plugin_include').val()};
            jQuery('#nn_loader_extension').css('display','none');
            transactionRequestHandler(extensionResponseParams, jQuery('#nn_admin_url').val() +'transactions.php?', jQuery('#nn_admin_url').val() + 'transactions.php', 'orders');
        break;

        case 'autofill':
     
            jQuery('#nn_loader').css('display','none');
            apiAutofillDetails(decodeURIComponent(result));
        break;

        case 'orders':
            jQuery('#nn_loader').css('display','none');
            jQuery('#admin_order_display_block').css({display:'block', position:'fixed'});
            jQuery('#admin_order_display_block').html(result);
        break;
    }
}

function apiAutofillDetails(autoconfigResponseParams)
{
    var fillParams = jQuery.parseJSON(autoconfigResponseParams);
    
    if (fillParams.status != 100) {
        jQuery('input[name="novalnet_public_key"]').val('');
        alert(fillParams.config_result);
        return false;
    }
    
    var tariffKeys = Object.keys(fillParams.tariff);

    var saved_tariff_id = jQuery('#tariffid').val();
    var tariff_id;

    try {
        var select_text = decodeURIComponent(escape('Auswählen'));
    } catch(e) {
        var select_text = 'Auswählen';
    }

    jQuery('#tariffid').replaceWith('<select id="tariffid" class="form-control combo" name="tariffid"><option value="" disabled>'+select_text+'</option></select>');

    jQuery('#tariffid').find('option').remove();
    
   for (var i = 0; i < tariffKeys.length; i++) 
   {
        if (tariffKeys[i] !== undefined) {          
        
        jQuery('#tariffid').append(jQuery('<option>', {
                value: jQuery.trim(tariffKeys[i])+'-'+  jQuery.trim(fillParams.tariff[tariffKeys[i]].type),
                text : jQuery.trim(fillParams.tariff[tariffKeys[i]].name)
            }));

        }

        if (saved_tariff_id == jQuery.trim(tariffKeys[i])+'-'+  jQuery.trim(fillParams.tariff[tariffKeys[i]].type)) {
            jQuery('#tariffid').val(jQuery.trim(tariffKeys[i])+'-'+  jQuery.trim(fillParams.tariff[tariffKeys[i]].type));
        }
       
    }
   
    jQuery('input[name=vendorid]').val(fillParams.vendor);
    jQuery('input[name=authcode]').val(fillParams.auth_code);
    jQuery('input[name=productid]').val(fillParams.product);   
    jQuery('input[name=key_password]').val(fillParams.access_key);
  
}

function isValidDate(dueDate)
{
    if (dueDate == '')
        return false;

    var rxDatePattern = /^(\d{4})(\/|-)(\d{1,2})(\/|-)(\d{1,2})$/;
    var dtArray = dueDate.match(rxDatePattern);

    if (dtArray == null)
        return false;

    dtMonth = dtArray[3];
    dtDay   = dtArray[5];
    dtYear  = dtArray[1];

    if ((dtMonth < 1 || dtMonth > 12) || (dtDay < 1 || dtDay> 31) || ((dtMonth == 4 || dtMonth == 6 || dtMonth == 9 || dtMonth == 11) && dtDay == 31)) {
        return false;
    }
    else if (dtMonth == 2) {
        var isleap = (dtYear % 4 == 0 && (dtYear % 100 != 0 || dtYear % 400 == 0));

        if (dtDay > 29 || (dtDay == 29 && !isleap))
            return false;
    }
    return true;
}

function isAlphanumeric(event)
{
    var keycode = ('which' in event) ? event.which : event.keyCode;
    event   = event || window.event;
    var reg =  /^[a-z0-9]+$/i;
    return (reg.test(String.fromCharCode(keycode)) || keycode == 0 || keycode == 8);
}

function emptyMandatoryValues()
{
    jQuery('input[name="vendorid"]').val('');
    jQuery('#tariffid').val('');
    jQuery('input[name="authcode"]').val('');
    jQuery('input[name="productid"]').val('');
    jQuery('input[name="key_password"]').val('');
}
