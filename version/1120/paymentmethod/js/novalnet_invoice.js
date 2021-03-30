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
 * Novalnet Invoice script
*/

jQuery(document).ready(function () {

    var paymentFormId = jQuery('#nn_payment').closest('form').attr('id');

    jQuery('#'+paymentFormId).submit(function () {

        if (jQuery('#nn_pin').length) {
            var nn_pin = jQuery.trim(jQuery('#nn_pin').val());

            if (nn_pin == '' && !(jQuery('#nn_forgot_pin').is(':checked'))) {
                alert(jQuery('#nn_pin_empty_error_message').val());
                return false;
            } else if ((/^\s+|\s+$|([\/\\#,+@!^()$~%.":*?<>{}])/g).test(nn_pin) && !(jQuery('#nn_forgot_pin').is(':checked'))) {
                alert(jQuery('#nn_pin_error_message').val());
                return false;
            }
        }

        if (jQuery('#nn_dob').length && jQuery('#nn_guarantee_force').val() != '1') {
            var nn_dob = jQuery.trim(jQuery('#nn_dob').val());

            if (nn_dob == '') {
                alert(jQuery('#nn_dob_error_message').val());
                return false;
            } else if (!(/^(\d{2})[.\/](\d{2})[.\/](\d{4})$/).test(nn_dob)) {
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
    });
});
