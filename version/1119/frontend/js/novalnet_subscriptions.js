/*
 * Novalnet subscription file script
 * By Novalnet (https://www.novalnet.de)
 * Copyright (c) Novalnet
*/

jQuery(document).ready(function () {
    if (jQuery('#nn_mobile_version').val() == true) {
        jQuery('#content .container .ui-last-child p').parent().removeClass('ui-last-child').after(jQuery('#nn_subs_content').val());
    } else {
        jQuery('#content p:last-child').last().after().append(jQuery('#nn_subs_content').val());
    }
});

function subscriptionCancel()
{
    var termination_reason = jQuery('#subscribe_termination_reason').val();

    if (termination_reason == '' || termination_reason == null) {
        alert(jQuery('#nn_subs_error').val());
        return false;
    }

    var subscriptionCancelRequestParams = { 'orderNo' : jQuery('#nn_order_no').val() , 'apiStatus' : 'subsCancellation', 'subsReason' : termination_reason, 'pluginInc' : jQuery('#nn_global_include_url').val() };
    jQuery('#subs_cancel').attr({'disabled':'disabled'});
    subscriptionRequestHandler(subscriptionCancelRequestParams);
}

function subscriptionRequestHandler(subscriptionCancelRequestParams)
{
    jQuery('#nn_loader').show();

    if ('XDomainRequest' in window && window.XDomainRequest !== null) {
        var xdr = new XDomainRequest(); // Use Microsoft XDR
        var subscriptionCancelRequestParams = jQuery.param(subscriptionCancelRequestParams);
        xdr.open('POST', jQuery('#nn_plugin_url').val());
        xdr.onload = function (result) {
            jQuery('#nn_loader').hide();
            alert(result);
            window.location.reload();
        };
        xdr.onerror = function () {
            _result = false;
        };
        xdr.send(subscriptionCancelRequestParams);
    } else {
        jQuery.ajax({
            url      : jQuery('#nn_plugin_url').val(),
            type     : 'post',
            dataType : 'html',
            data     : subscriptionCancelRequestParams,
            success  : function (result) {
                jQuery('#nn_loader').hide();
                alert(result);
                window.location.reload();
            }
        });
    }
}
