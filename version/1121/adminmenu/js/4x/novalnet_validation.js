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
 * Novalnet admin validation script
*/

/**************** Novalnet Javascript file for handling admin validation operations *****/

jQuery(document).ready(function() {

    var paymentSettings = jQuery('.tab-content').children()[2];

    jQuery(paymentSettings).find('.panel .panel-body').hide();

    jQuery(paymentSettings).find('.panel-heading').append('<i class="fa fa-chevron-circle-down nn_fa"></i>');

    jQuery(paymentSettings).find('.panel').hover(function(){
        jQuery(this).css('cursor', 'pointer');
    });

    jQuery.each(['cc', 'sepa', 'invoice', 'paypal'],function(index, value) {
        if(jQuery(paymentSettings).find('#novalnet_'+value+'_payment_action').val() == 0) {
            
                jQuery(paymentSettings).find('#novalnet_'+value+'_manual_check_limit').parent().parent().hide(); 
        }
        jQuery(paymentSettings).find('#novalnet_'+value+'_payment_action').on('change',function(event){
            if(jQuery(paymentSettings).find('#novalnet_'+value+'_payment_action').val() == 0) {
                jQuery(paymentSettings).find('#novalnet_'+value+'_manual_check_limit').parent().parent().hide();
            } else {
                jQuery(paymentSettings).find('#novalnet_'+value+'_manual_check_limit').parent().parent().show();
            }
        });     
    });

    jQuery.each(['#novalnet_invoice_guarantee', '#novalnet_sepa_guarantee'], function(index, element) {
        jQuery(element).closest('.panel-body').prepend(decodeTextMessage('<div class="input-group"><span class="input-group-addon nn_additional_span"><b><h5>Grundanforderungen für die Zahlungsgarantie</h5><br>Zugelassene Staaten: AT, DE, CH<br> Zugelassene Währung: EUR<br>Mindestbetrag der Bestellung >= 9,99 EUR<br>Mindestalter des Endkunden >= 18 Jahre<br>Rechnungsadresse und Lieferadresse müssen übereinstimmen<br>Geschenkgutscheine / Coupons sind nicht erlaubt</b></span></div>'));
    });

    jQuery(paymentSettings).find('.panel-heading').click(function(event){
        event.stopImmediatePropagation();
        var headingDiv = this;
        
        // Payment Link Array        
        var payment_link_container = {'novalnet_cc_payment_link':'https://www.novalnet.de/zahlungsart-kreditkarte', 'novalnet_sepa_payment_link':'https://www.novalnet.de/sepa-lastschrift', 'novalnet_invoice_payment_link':'https://www.novalnet.de/kauf-auf-rechnung-online-payment', 
        'novalnet_prepayment_payment_link':'https://www.novalnet.de/vorkasse-internet-payment', 'novalnet_cashpayment_payment_link':'https://www.novalnet.de/barzahlen', 'novalnet_banktransfer_payment_link':'https://www.novalnet.de/online-ueberweisung-sofortueberweisung', 'novalnet_ideal_payment_link':'https://www.novalnet.de/ideal-online-ueberweisung', 'novalnet_eps_payment_link':'https://www.novalnet.de/eps-online-ueberweisung', 'novalnet_giropay_payment_link':'https://www.novalnet.de/giropay', 'novalnet_paypal_payment_link':'https://www.novalnet.de/mit-paypal-weltweit-sicher-verkaufen', 'novalnet_przelewy24_payment_link':'https://www.novalnet.de/przelewy24'};
        
        jQuery(headingDiv).parent().children('.panel-body').slideToggle(function() {
            var nn_fa_element = jQuery(headingDiv).find('i');
            if (nn_fa_element.hasClass('fa-chevron-circle-down')) {
                
                // Gets ID from the Panel Text
                jQuery(nn_fa_element).switchClass( 'fa-chevron-circle-down', 'fa-chevron-circle-up');                
              
                // Payment Link Generation for Each Payments
                jQuery.each(['cc', 'sepa', 'invoice', 'prepayment', 'cashpayment', 'banktransfer', 'ideal', 'eps', 'giropay', 'paypal', 'przelewy24'],function(index, payment_name) {
                    var payment_link = 'novalnet_'+payment_name+'_payment_link';
                    // Checks the existance of ID                
                    // if(!(jQuery('#' + payment_link).length)) {
                    //      jQuery('#novalnet_'+payment_name+'_enablemode').parent().parent().parent('.panel-body').prepend('<div><a id="' + payment_link + '" href="' + payment_link_container [ payment_link ] + '" target="_blank" >Zahlungs link</a></div>'); 
                    // }
              });
            } else {
                jQuery(nn_fa_element).switchClass( 'fa-chevron-circle-up', 'fa-chevron-circle-down');
            }
        });
    });

    var container = {'#novalnet_cc_set_order_status': '#novalnet_cc_form_label', '#novalnet_sepa_pin_amount' : '#novalnet_sepa_guarantee', '#novalnet_invoice_pin_amount' : '#novalnet_invoice_guarantee', '#novalnet_cc_form_css' : '#novalnet_cc_cardholder_label, #novalnet_cc_cardnumber_label, #novalnet_cc_cardexpiry_label, #novalnet_cc_cardcvc_label'};

    jQuery.each(container, function(target, element) {
        styleContainers(target, element);
    });

    jQuery('#sepa_due_date, #novalnet_cc_manual_check_limit, #novalnet_sepa_manual_check_limit, #novalnet_invoice_manual_check_limit, #novalnet_paypal_manual_check_limit, #novalnet_invoice_guarantee_min_amount, #novalnet_sepa_guarantee_min_amount, #novalnet_public_key').parent().on('change', function() {
        if (jQuery(this).hasClass('set_error')) jQuery(this).removeClass('set_error');
    });

    jQuery('#novalnet_paypal_extensive_option').closest('div').after(decodeTextMessage('<br><span class="nn_paypal_notify" style="color:red; margin-bottom:5px; display:none;">Um diese Option zu verwenden, müssen Sie die Option Billing Agreement (Zahlungsvereinbarung) in Ihrem PayPal-Konto aktiviert haben. Kontaktieren Sie dazu bitte Ihren Kundenbetreuer bei PayPal</span>'));

    jQuery('#novalnet_paypal_extensive_option').on('change', function(event){
        jQuery('.nn_paypal_notify').css('display', 'none');
        if (this.value != 0) jQuery('.nn_paypal_notify').css('display', 'block');
    });

    if (jQuery('#novalnet_paypal_extensive_option').val() != 0) {
        jQuery('.nn_paypal_notify').css('display', 'block');
    }

    jQuery('button[name=speichern]').on('click', function(event){
        performAdminValidations(event);
    });
});

function styleContainers(target, element) {
    jQuery(target).closest('div').after(jQuery(element).closest('.panel'));
}

function performAdminValidations(event) {
    if (jQuery('#content').find('.tab-settings-0').hasClass('active')) {
        var validatedField = '';

        if (jQuery.trim(jQuery('#novalnet_public_key').val()) == '') {
            validatedField = 'novalnet_public_key';
        } 

        if (validatedField != '') {
            event.preventDefault();
            event.stopPropagation();
            alert(decodeTextMessage( 'Füllen Sie bitte alle Pflichtfelder aus'));
            handleErrorElement(jQuery('#'+validatedField).parent());
        }

    } else if (jQuery('#content').find('.tab-settings-1').hasClass('active')) {
        if (jQuery.trim(jQuery('#sepa_due_date').val()) != '' && (isNaN(jQuery('#sepa_due_date').val()) || jQuery('#sepa_due_date').val() < 2 || jQuery('#sepa_due_date').val() > 14)) {
            event.preventDefault();
            alert(decodeTextMessage('SEPA Fälligkeitsdatum Ungültiger'));
            handleErrorElement(jQuery('#sepa_due_date').parent());
        }

        if ((jQuery.trim(jQuery('#invoice_duration').val()) != '' && isNaN(jQuery('#invoice_duration').val())) || jQuery('#invoice_duration').val().indexOf(".") != -1) {
            event.preventDefault();
            alert(decodeTextMessage('Geben Sie bitte ein gültiges Ablaufdatum für den Zahlschein ein.'));
        }

        if ((jQuery.trim(jQuery('#cashpayment_slip_expiry').val()) != '' && isNaN(jQuery('#cashpayment_slip_expiry').val())) || jQuery('#cashpayment_slip_expiry').val().indexOf(".") != -1) {
            event.preventDefault();
            alert(decodeTextMessage('Geben Sie bitte ein gültiges Ablaufdatum für den Zahlschein ein.'));
        }

        if (
            ((jQuery.trim(jQuery('#novalnet_sepa_pin_amount').val()) == '' && isNaN(jQuery('#novalnet_sepa_pin_amount').val())) || jQuery('#novalnet_sepa_pin_amount').val().indexOf(".") != -1) ||
            ((jQuery.trim(jQuery('#novalnet_invoice_pin_amount').val()) == '' && isNaN(jQuery('#novalnet_invoice_pin_amount').val())) || jQuery('#novalnet_invoice_pin_amount').val().indexOf(".") != -1)
        ) {
            event.preventDefault();
            alert(decodeTextMessage('Ungültiger Betrag'));
        }

        jQuery.each(['invoice', 'sepa'],function(index, value) {
            if (jQuery('#novalnet_'+value+'_guarantee').val() == 1) {

                var minimum_guarantee_amount = jQuery.trim(jQuery('#novalnet_'+value+'_guarantee_min_amount').val());

                if (minimum_guarantee_amount.indexOf(".") != -1) {
                    event.preventDefault();
                    alert(decodeTextMessage('Ungültiger Betrag'));
                } else if (minimum_guarantee_amount != '' && minimum_guarantee_amount < 999) {
                    event.preventDefault();
                    event.stopPropagation();
                    alert(decodeTextMessage('Der Mindestbetrag sollte bei mindestens 9,99 EUR liegen'));
                    handleErrorElement(jQuery('#novalnet_'+value+'_guarantee_min_amount').parent());
                }
            }
        });

            jQuery.each(['cc', 'sepa', 'invoice', 'paypal'],function(index, value) {
                if (jQuery('#novalnet_'+value+'_manual_check_limit').val().indexOf(".") != -1) {
                    event.preventDefault();
                    alert(decodeTextMessage('Ungültiger Betrag'));
                } else if (jQuery.trim(jQuery('#novalnet_'+value+'_manual_check_limit').val()) != '' && isNaN(jQuery('#novalnet_'+value+'_manual_check_limit').val())) {
                    event.preventDefault();
                    event.stopPropagation();
                    alert(decodeTextMessage('Füllen Sie bitte alle Pflichtfelder aus'));
                    handleErrorElement(jQuery('#novalnet_'+value+'_manual_check_limit').parent());
                }
            });
        
    }
}

function handleErrorElement(element, setclass) {
    jQuery('html, body').animate({
        scrollTop: (element.offset().top - 160)
        }, 500, function() {

        if (setclass !== false) {
            jQuery(element).addClass('set_error');
        }

        if (jQuery(element).closest('div').css('display') == 'none') {
            jQuery(this).css('display','block');
        }
    });
}

function decodeTextMessage(text) {
    try {
        return decodeURIComponent(escape(text));
    }
    catch(err) {
        alert(err.message);
    }
}
