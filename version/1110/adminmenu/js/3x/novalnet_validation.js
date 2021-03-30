/*
 * Novalnet admin validation script
 * By Novalnet (https://www.novalnet.de)
 * Copyright (c) Novalnet
*/

/**************** Novalnet Javascript file for handling admin validation operations *****/

jQuery(document).ready(function() {

	var container = {'novalnet_cc_reference2': ['novalnet_cc_form_label', 'input'], 'novalnet_sepa_reference2' : ['novalnet_sepa_guarantee', 'select'], 'novalnet_invoice_reference2' : ['novalnet_invoice_guarantee', 'select'], 'novalnet_cc_form_css' : ['novalnet_cc_cardholder_label', 'input'], 'novalnet_cc_cardholder_input' : ['novalnet_cc_cardnumber_label', 'input'], 'novalnet_cc_cardnumber_input' : ['novalnet_cc_cardexpiry_label', 'input'], 'novalnet_cc_cardexpiry_input' : ['novalnet_cc_cardcvc_label', 'input']};

	jQuery.each(container, function(target, element) {
        styleContainers(target, element);
    });

    var paymentTab = jQuery('#Novalnet-Zahlungseinstellungen');

	jQuery(paymentTab).find('div.item').hide();
	jQuery(paymentTab).find('div.nn_internaldiv').hide();

	jQuery('#Novalnet-Zahlungseinstellungen div.category').append('<span class="nn_expand"></span>');

	jQuery.each(['select[name=novalnet_invoice_guarantee]', 'select[name=novalnet_sepa_guarantee]'], function(index, element) {
		jQuery(element).parent().parent().prepend(decodeTextMessage('<div class="nn_additional_div"><b><h4>Grundanforderungen für die Zahlungsgarantie</h4><br>Zugelassene Staaten: AT, DE, CH<br> Zugelassene Währung: EUR<br>Mindestbetrag der Bestellung >= 20,00 EUR<br>Maximalbetrag der Bestellung <= 5.000,00 EUR<br>Mindestalter des Endkunden >= 18 Jahre<br>Rechnungsadresse und Lieferadresse müssen übereinstimmen<br>Geschenkgutscheine / Coupons sind nicht erlaubt</b></div>'));
	});

	jQuery(paymentTab).find('div.category').hover(function(){
		jQuery(this).css('cursor', 'pointer');
		var headingDiv = this;
		jQuery(this).on('click', function(event) {
			var toggleSpan = jQuery(headingDiv).find('span');
			if (toggleSpan.hasClass('nn_expand')) {
				jQuery(toggleSpan).switchClass('nn_expand', 'nn_retract');
				event.stopPropagation();
			} else {
				jQuery(toggleSpan).switchClass('nn_retract', 'nn_expand');
				event.stopPropagation();
			}
		});
	});

	jQuery(paymentTab).find('div.category:not(.nn_internaldiv)').click(function(event){
		var headingDiv = this;
		var nextCategory = jQuery.trim(jQuery(headingDiv).text()) == 'Przelewy24' ? 'save_wrapper' : 'category';
		jQuery(headingDiv).nextUntil(jQuery('div.'+nextCategory+':not(.nn_internaldiv)')).slideToggle(function() {
			jQuery(paymentTab).find('div.nn_internalitems').hide();
		});
	});

	jQuery(paymentTab).find('div.nn_internaldiv').click(function(event) {
		var headingDiv = this;
		jQuery(this).nextUntil(jQuery('div:not(.nn_internalitems')).slideToggle();
	});

	var validationElements = ['sepa_due_date', 'manual_check_limit', 'novalnet_invoice_guarantee_min_amount', 'novalnet_invoice_guarantee_max_amount', 'novalnet_sepa_guarantee_min_amount', 'novalnet_sepa_guarantee_max_amount', 'tariff_period', 'tariff_period2', 'tariff_period2_amount', 'novalnet_public_key'];

	jQuery.each(validationElements, function(index, element) {
		var inputElement = jQuery('input[name='+element);
        jQuery(inputElement).on('change', function() {
			if (jQuery(inputElement).hasClass('set_error')) {
				jQuery(inputElement).removeClass('set_error');
			}
		});
    });

    jQuery('select[name=novalnet_paypal_extensive_option]').parent().append(decodeTextMessage('<br><span class="nn_paypal_notify" style="color:red; display:none;">Um diese Option zu verwenden, müssen Sie die Option Billing Agreement (Zahlungsvereinbarung) in Ihrem PayPal-Konto aktiviert haben. Kontaktieren Sie dazu bitte Ihren Kundenbetreuer bei PayPal</span>'));

    jQuery('select[name=novalnet_paypal_extensive_option]').on('change', function(event){
		if (this.value != 0) {
			jQuery('.nn_paypal_notify').css('display', 'block');
		} else {
			jQuery('.nn_paypal_notify').css('display', 'none');
		}
	});

    if (jQuery('select[name=novalnet_paypal_extensive_option]').val() != 0) {
		jQuery('.nn_paypal_notify').css('display', 'block');
	}

	jQuery('input[name=speichern]').on('click', function(event){
		performAdminValidations(event);
	});
});

function styleContainers(target, element) {

	var inputType = jQuery.inArray(target, ['cc_maestro_accept', 'novalnet_invoice_testmode']) === -1 ? 'input' : 'select';

	var internalDiv = jQuery(element[1]+'[name='+element[0]+']').parent().parent().prev();

	internalDiv.addClass('nn_internaldiv');

	jQuery(inputType+'[name='+target+']').parent().parent().after(jQuery(internalDiv).nextUntil('div.category').addClass('nn_internalitems').andSelf());
}

function performAdminValidations(event) {

	if (jQuery('.tabbernav li:eq(1)').hasClass('tabberactive')) {
		var validatedField = '';

		if (jQuery.trim(jQuery('input[name=novalnet_public_key]').val()) == '') {
			validatedField = 'novalnet_public_key';
		} else if (jQuery.trim(jQuery('input[name=tariff_period]').val()) != '' && !((/^\d+(d|m|y){1}$/).test(jQuery('input[name=tariff_period]').val()))) {
			validatedField = 'tariff_period';
		} else if (jQuery.trim(jQuery('input[name=tariff_period2]').val()) != '' && jQuery.trim(jQuery('input[name=tariff_period2_amount]').val()) == '') {
			validatedField = 'tariff_period2_amount';
		} else if (jQuery.trim(jQuery('input[name=tariff_period2_amount]').val()) != '' && !isNaN(jQuery('input[name=tariff_period2_amount]').val()) && !((/^\d+(d|m|y){1}$/).test(jQuery('input[name=tariff_period2]').val()))) {
			validatedField = 'tariff_period2';
		} else if (jQuery.trim(jQuery('input[name=manual_check_limit]').val()) != '' && isNaN(jQuery('input[name=manual_check_limit]').val())) {
			validatedField = 'manual_check_limit';
		}

		if (validatedField != '') {
            event.preventDefault();
			alert(decodeTextMessage(jQuery.inArray(validatedField, ['tariff_period', 'tariff_period2']) == 0 ? 'Geben Sie bitte eine gültige Abonnementsperiode ein (z.B. 1d/1m/1y)' : 'Füllen Sie bitte alle Pflichtfelder aus'));
			handleErrorElement(jQuery('input[name='+validatedField+']'));
		}

	} else if (jQuery('.tabbernav li:eq(2)').hasClass('tabberactive')) {
		if (jQuery.trim(jQuery('input[name=sepa_due_date]').val()) != '' && (isNaN(jQuery('input[name=sepa_due_date]').val()) || jQuery('input[name=sepa_due_date]').val() < 7)) {
			event.preventDefault();
			alert(decodeTextMessage('SEPA Fälligkeitsdatum Ungültiger'));
			handleErrorElement(jQuery('input[name=sepa_due_date]'));
		} else {
			jQuery.each(['invoice', 'prepayment'],function(index, value) {
				if (jQuery('select[name=novalnet_'+value+'_enablemode]').val() == 1 && jQuery('select[name=novalnet_'+value+'_payment_reference1]').val() == 0 && jQuery('select[name=novalnet_'+value+'_payment_reference2]').val() == 0 && jQuery('select[name=novalnet_'+value+'_payment_reference3]').val() == 0) {
					event.preventDefault();
					event.stopPropagation();
					alert(decodeTextMessage('Zahlungsreferenz fehlt oder ist ungültig'));
					handleErrorElement(jQuery('select[name=novalnet_'+value+'_payment_reference1]'), false);
				}
			});

			jQuery.each(['invoice', 'sepa'],function(index, value) {
				if (jQuery('select[name=novalnet_'+value+'_guarantee]').val() == 1) {
					var minimum_guarantee_amount = jQuery.trim(jQuery('input[name=novalnet_'+value+'_guarantee_min_amount]').val());
					var maximum_guarantee_amount = jQuery.trim(jQuery('input[name=novalnet_'+value+'_guarantee_max_amount]').val());

					if (minimum_guarantee_amount != '' && (minimum_guarantee_amount < 2000 || minimum_guarantee_amount > 500000)) {
						event.preventDefault();
						event.stopPropagation();
						alert(decodeTextMessage('Der Mindestbetrag sollte bei mindestens 20,00 EUR liegen, jedoch nicht mehr als 5.000,00 EUR'));
						handleErrorElement(minimum_guarantee_amount);
					} else if (maximum_guarantee_amount != '' && ((maximum_guarantee_amount < 2000 || maximum_guarantee_amount > 500000) || (minimum_guarantee_amount != '' && (parseInt(maximum_guarantee_amount) < parseInt(minimum_guarantee_amount))))) {
						event.preventDefault();
						event.stopPropagation();
						alert(decodeURIComponent('Der Maximalbetrag sollte gr%C3%B6%C3%9Fer sein als der Mindestbestellbetrag, jedoch nicht h%C3%B6her als 5.000,00 EUR'));
						handleErrorElement(maximum_guarantee_amount);
					}
				}
			});
		}
	}
}

function handleErrorElement(element, setclass) {
	jQuery('html, body').animate({
		scrollTop: (element.offset().top - 160)
		}, 500, function() {

		if (setclass !== false) {
			jQuery(element).addClass('set_error');
		}
		var ancestorElement = jQuery(element).parent().parent();
		if (ancestorElement.css('display') == 'none') {
			jQuery(ancestorElement).css('display','block');
		}
	});
}

function decodeTextMessage(text) {
	return decodeURIComponent(escape(text));
}
