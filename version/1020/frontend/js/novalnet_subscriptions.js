/*
 * Novalnet subscription file script
 * By Novalnet (http://www.novalnet.de)
 * Copyright (c) Novalnet
*/
if (typeof(jQuery) == 'undefined') {
    var s  = document.createElement('script');
    s.type = 'text/javascript';
    s.src  = '/includes/plugins/novalnetag/version/1020/paymentmethod/js/jquery.js';
    document.getElementsByTagName('head')[0].appendChild(s);
}

jQuery(document).ready(function() {
    jQuery('#content p:last-child').last().after().append(jQuery('#nn_subs_content').val());
});

function subscriptionCancel()
{
    var termination_reason = jQuery('#subscribe_termination_reason').val();

    if (termination_reason == '' || termination_reason == null) {
        alert(jQuery('#nn_subs_error').val());
        return false;
    }

    var subscriptionCancelRequestParams = { 'orderNo' : jQuery('#orderno').val() , 'apiStatus' : 'subsCancellation', 'subsReason' : termination_reason, 'frontEnd' : 1, 'pluginInc' : jQuery('#nn_global_include_url').val() };
    subscriptionRequestHandler(subscriptionCancelRequestParams);
}

function subscriptionRequestHandler(subscriptionCancelRequestParams)
{
    jQuery('#nn_loader').css('display','block');

    if ('XDomainRequest' in window && window.XDomainRequest !== null) {
        var xdr = new XDomainRequest(); // Use Microsoft XDR
        var subscriptionCancelRequestParams = jQuery.param(subscriptionCancelRequestParams);
        xdr.open('POST', jQuery('#nn_plugin_url').val());
        xdr.onload = function (result) {
            jQuery('#nn_loader').css('display','none');
            alert(result);
            window.location.reload();
        };
        xdr.onerror = function() {
            _result = false;
        };
        xdr.send(subscriptionCancelRequestParams);
    } else {
        jQuery.ajax({
            url      : jQuery('#nn_plugin_url').val(),
            type     : 'post',
            dataType : 'html',
            data     : subscriptionCancelRequestParams,
            success  : function (result)
            {
                jQuery('#nn_loader').css('display','none');
                alert(result);
                window.location.reload();
            }
        });
    }
}
