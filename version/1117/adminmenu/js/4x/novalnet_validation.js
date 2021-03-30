/*
 * Novalnet admin validation script
 * By Novalnet (https://www.novalnet.de)
 * Copyright (c) Novalnet
*/

/**************** Novalnet Javascript file for handling admin validation operations *****/

jQuery(document).ready(function () {

    var paymentSettings = jQuery('.tab-content').children()[2];

    jQuery(paymentSettings).find('.panel .panel-body').hide();

    jQuery(paymentSettings).find('.panel-heading').append('<i class="fa fa-chevron-circle-down nn_fa"></i>');

    jQuery(paymentSettings).find('.panel').hover(function () {
        jQuery(this).css('cursor', 'pointer');
    });

    jQuery.each(['#novalnet_invoice_guarantee', '#novalnet_sepa_guarantee'], function (index, element) {
        jQuery(element).closest('.panel-body').prepend(decodeTextMessage('<div class="input-group"><span class="input-group-addon nn_additional_span"><b><h5>Grundanforderungen für die Zahlungsgarantie</h5><br>Zugelassene Staaten: AT, DE, CH<br> Zugelassene Währung: EUR<br>Mindestbetrag der Bestellung >= 20,00 EUR<br>Mindestalter des Endkunden >= 18 Jahre<br>Rechnungsadresse und Lieferadresse müssen übereinstimmen<br>Geschenkgutscheine / Coupons sind nicht erlaubt</b></span></div>'));
    });

    jQuery(paymentSettings).find('.panel-heading').click(function (event) {
        event.stopImmediatePropagation();
        var headingDiv = this;
        jQuery(headingDiv).parent().children('.panel-body').slideToggle(function () {
            var nn_fa_element = jQuery(headingDiv).find('i');
            if (nn_fa_element.hasClass('fa-chevron-circle-down')) {
                jQuery(nn_fa_element).switchClass('fa-chevron-circle-down', 'fa-chevron-circle-up');
            } else {
                jQuery(nn_fa_element).switchClass('fa-chevron-circle-up', 'fa-chevron-circle-down');
            }
        });
    });

    var container = {'#novalnet_cc_set_order_status': '#novalnet_cc_form_label', '#novalnet_sepa_pin_amount' : '#novalnet_sepa_guarantee', '#novalnet_invoice_pin_amount' : '#novalnet_invoice_guarantee', '#novalnet_cc_form_css' : '#novalnet_cc_cardholder_label, #novalnet_cc_cardnumber_label, #novalnet_cc_cardexpiry_label, #novalnet_cc_cardcvc_label'};

    jQuery.each(container, function (target, element) {
        styleContainers(target, element);
    });

    jQuery('#sepa_due_date, #novalnet_cc_manual_check_limit, #novalnet_sepa_manual_check_limit, #novalnet_invoice_manual_check_limit, #novalnet_paypal_manual_check_limit, #novalnet_invoice_guarantee_min_amount, #novalnet_sepa_guarantee_min_amount, #tariff_period, #tariff_period2, #tariff_period2_amount, #novalnet_public_key').parent().on('change', function () {
        if (jQuery(this).hasClass('set_error')) {
            jQuery(this).removeClass('set_error');
        }
    });

    jQuery('#novalnet_paypal_extensive_option').closest('div').after(decodeTextMessage('<br><span class="nn_paypal_notify" style="color:red; margin-bottom:5px; display:none;">Um diese Option zu verwenden, müssen Sie die Option Billing Agreement (Zahlungsvereinbarung) in Ihrem PayPal-Konto aktiviert haben. Kontaktieren Sie dazu bitte Ihren Kundenbetreuer bei PayPal</span>'));

    jQuery('#novalnet_paypal_extensive_option').on('change', function (event) {
        jQuery('.nn_paypal_notify').css('display', 'none');
        if (this.value != 0) {
            jQuery('.nn_paypal_notify').css('display', 'block');
        }
    });

    if (jQuery('#novalnet_paypal_extensive_option').val() != 0) {
        jQuery('.nn_paypal_notify').css('display', 'block');
    }

    jQuery('button[name=speichern]').on('click', function (event) {
        performAdminValidations(event);
    });
});

function styleContainers(target, element)
{
    jQuery(target).closest('div').after(jQuery(element).closest('.panel'));
}

function performAdminValidations(event)
{
    if (jQuery('#content').find('.tab-settings-0').hasClass('active')) {
        var validatedField = '';

        if (jQuery.trim(jQuery('#novalnet_public_key').val()) == '') {
            validatedField = 'novalnet_public_key';
        } else if (jQuery.trim(jQuery('#tariff_period2').val()) != '' && !((/^\d+(d|m|y){1}$/).test(jQuery('#tariff_period').val()))) {
            validatedField = 'tariff_period';
        } else if (jQuery.trim(jQuery('#tariff_period2').val()) != '' && jQuery.trim(jQuery('#tariff_period2_amount').val()) == '') {
            validatedField = 'tariff_period2_amount';
        } else if (jQuery.trim(jQuery('#tariff_period2_amount').val()) != '' && !isNaN(jQuery('#tariff_period2_amount').val())  && !((/^\d+(d|m|y){1}$/).test(jQuery('#tariff_period2').val()))) {
            validatedField = 'tariff_period2';
        }

        if (validatedField != '') {
            event.preventDefault();
            event.stopPropagation();
            alert(decodeTextMessage(jQuery.inArray(validatedField, ['tariff_period', 'tariff_period2']) == 0 ? 'Geben Sie bitte eine gültige Abonnementsperiode ein (z.B. 1d/1m/1y)' : 'Füllen Sie bitte alle Pflichtfelder aus'));
            handleErrorElement(jQuery('#'+validatedField).parent());
        }
    } else if (jQuery('#content').find('.tab-settings-1').hasClass('active')) {
        if (jQuery.trim(jQuery('#sepa_due_date').val()) != '' && (isNaN(jQuery('#sepa_due_date').val()) || jQuery('#sepa_due_date').val() < 7)) {
            event.preventDefault();
            alert(decodeTextMessage('SEPA Fälligkeitsdatum Ungültiger'));
            handleErrorElement(jQuery('#sepa_due_date').parent());
        } else {
            jQuery.each(['invoice', 'prepayment'],function (index, value) {
                if (jQuery('#novalnet_'+value+'_enablemode').val() == 1 && jQuery('#novalnet_'+value+'_payment_reference1').val() == 0 && jQuery('#novalnet_'+value+'_payment_reference2').val() == 0 && jQuery('#novalnet_'+value+'_payment_reference3').val() == 0) {
                    event.preventDefault();
                    event.stopPropagation();
                    alert(decodeTextMessage('Wählen Sie mindestens einen Verwendungszweck aus'));
                    handleErrorElement(jQuery('#novalnet_'+value+'_payment_reference1').parent(), false);
                }
            });

            jQuery.each(['invoice', 'sepa'],function (index, value) {
                if (jQuery('#novalnet_'+value+'_guarantee').val() == 1) {
                    var minimum_guarantee_amount = jQuery.trim(jQuery('#novalnet_'+value+'_guarantee_min_amount').val());
                    if (minimum_guarantee_amount != '' && minimum_guarantee_amount < 2000) {
                        event.preventDefault();
                        event.stopPropagation();
                        alert(decodeTextMessage('Der Mindestbetrag sollte bei mindestens 20,00 EUR liegen.'));
                        handleErrorElement(jQuery('#novalnet_'+value+'_guarantee_min_amount').parent());
                    }
                }
            });

            jQuery.each(['cc', 'sepa', 'invoice', 'paypal'],function (index, value) {
                if (jQuery.trim(jQuery('#novalnet_'+value+'_manual_check_limit').val()) != '' && isNaN(jQuery('#novalnet_'+value+'_manual_check_limit').val())) {
                    event.preventDefault();
                    event.stopPropagation();
                    alert(decodeTextMessage('Füllen Sie bitte alle Pflichtfelder aus'));
                    handleErrorElement(jQuery('#novalnet_'+value+'_manual_check_limit').parent());
                }
            });
        }
    }
}

function handleErrorElement(element, setclass)
{
    jQuery('html, body').animate({
        scrollTop: (element.offset().top - 160)
        }, 500, function () {

            if (setclass !== false) {
                jQuery(element).addClass('set_error');
            }

            if (jQuery(element).closest('div').css('display') == 'none') {
                jQuery(this).css('display','block');
            }
        });
}

function decodeTextMessage(text)
{
    try {
        return decodeURIComponent(escape(text));
    } catch (err) {
        alert(err.message);
    }
}
