/*
 * Novalnet Credit Card Script
 * By Novalnet (https://www.novalnet.de)
 * Copyright (c) Novalnet AG
*/

jQuery(document).ready(function() {

    jQuery('#nn_payment_cc').css('display','block');

    var paymentFormId = jQuery('#nn_payment').closest('form').attr('id');

    if (jQuery('#one_click_shopping').val() && jQuery('#form_error').val() == '') {
        setSavedCardProcess();
    } else {
        setNewCardProcess();
    }

    jQuery('#nn_toggle_form').click(function() {
        if (jQuery('#nn_new_card_details').css('display') == 'block') {
            jQuery('#one_click_shopping').val(1);
            setSavedCardProcess();
        } else {
			getIframeHeight();
			setNewCardProcess();
        }
    });

    jQuery('#'+ paymentFormId).submit(function (evt) {
		if (jQuery('#nn_cc_hash').val() == '' && jQuery('#nn_new_card_details').css('display') == 'block') {
			evt.preventDefault();
			var iframe = jQuery('#novalnet_cc_iframe')[0];
			var iframeWindow= iframe.contentWindow ? iframe.contentWindow : iframe.contentDocument.defaultView;
			iframeWindow.postMessage({callBack : 'getHash'}, 'https://secure.novalnet.de');
		}
    });
});

jQuery(window).on('message', function(e) {

	var eventData = e.originalEvent;
	var targetOrigin = 'https://secure.novalnet.de';

	if (typeof eventData.data === 'string' ) {
        var data = eval('(' + eventData.data.replace(/(<([^>]+)>)/gi, "") + ')');
    }   else {
        var data = eventData.data;
    }

    if (eventData.origin === targetOrigin) {
        if (data['callBack'] == 'getHash') {
            if (data['result'] == 'success') {
				jQuery('#nn_cc_hash').val(data['hash']);
				jQuery('#nn_cc_uniqueid').val(data['unique_id']);
				var paymentForm = jQuery('#nn_payment').closest('form').attr('id');
				jQuery('#'+ paymentForm).submit();
            } else {
				alert(jQuery('<textarea />').html(data['error_message']).text());
            }
        } else if (data['callBack'] == 'getHeight') {
            jQuery('#novalnet_cc_iframe').height(data['contentHeight']);
        }
    }
});

jQuery(window).resize(function() {
	getIframeHeight();
});

function loadElements()
{
	var iframe = jQuery('#novalnet_cc_iframe')[0];
	var iframeWindow= iframe.contentWindow ? iframe.contentWindow : iframe.contentDocument.defaultView;

	var ccCustomFields = jQuery.parseJSON(jQuery('#nn_cc_formfields').val());

	var styleObj = {
		labelStyle : ccCustomFields.form_label,
		inputStyle : ccCustomFields.form_input,
		styleText  : ccCustomFields.form_css,
		card_holder : {
			labelStyle : ccCustomFields.cardholder_label,
			inputStyle : ccCustomFields.cardholder_input,
		},
		card_number : {
			labelStyle : ccCustomFields.cardnumber_label,
			inputStyle : ccCustomFields.cardnumber_input,
		},
		expiry_date : {
			labelStyle : ccCustomFields.cardexpiry_label,
			inputStyle : ccCustomFields.cardexpiry_input,
		},
		cvc       : {
			labelStyle : ccCustomFields.cardcvc_label,
			inputStyle : ccCustomFields.cardcvc_input,
		}
	};

	var textObj = {
		card_holder : {
			labelText : ccCustomFields.credit_card_name,
			inputText : ccCustomFields.credit_card_name_input,
		},
		card_number : {
			labelText : ccCustomFields.credit_card_number,
			inputText : ccCustomFields.credit_card_number_input,
		},
		expiry_date : {
			labelText : ccCustomFields.credit_card_date,
			inputText : ccCustomFields.credit_card_date_input,
		},
		cvc  : {
			labelText : ccCustomFields.credit_card_cvc,
			inputText : ccCustomFields.credit_card_cvc_input,
		},
		cvcHintText : ccCustomFields.credit_card_cvc_hint,
		errorText   : ccCustomFields.credit_card_error,
	};

	var requestObj = {
		callBack: 'createElements',
		customText: textObj,
		customStyle: styleObj
	};

	iframeWindow.postMessage(requestObj, 'https://secure.novalnet.de');
}

function getIframeHeight()
{
	var iframe = jQuery('#novalnet_cc_iframe')[0];
	var iframeWindow= iframe.contentWindow ? iframe.contentWindow : iframe.contentDocument.defaultView;
	iframeWindow.postMessage({callBack : 'getHeight'}, 'https://secure.novalnet.de');
}

function setSavedCardProcess()
{
	jQuery('#nn_toggle_form').html(jQuery('#nn_cc_display_text_new').val());
	jQuery('#nn_saved_details').show();
	jQuery('#nn_new_card_details').hide();
	jQuery('#nn_creditcard_desc').html(jQuery('#nn_cc_saved_desc').val());
}

function setNewCardProcess()
{
	jQuery('#nn_toggle_form').html(jQuery('#nn_cc_display_text_saved').val());
	jQuery('#one_click_shopping').val('');
	jQuery('#nn_saved_details').hide();
	jQuery('#nn_new_card_details').show();
	jQuery('#nn_creditcard_desc').html(jQuery('#is_cc3d_active').val() == 1 ? jQuery('#nn_cc_redirect_desc').val() : jQuery('#nn_cc_saved_desc').val());
}
