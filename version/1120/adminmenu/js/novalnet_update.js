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
 * Novalnet admin update page script
*/

jQuery(document).ready(function (evt) {
    jQuery('.nn_global_configuration').click(function (evt) {
        evt.preventDefault();
        var tab_id;
        if (jQuery('li').hasClass("tab-novalnet_update tab active")) {
            // inactive update tab
            jQuery('li.tab-novalnet_update.tab').removeClass("active");
            tab_id = jQuery('li.tab-novalnet_update.tab a').attr('href');
            jQuery(tab_id).removeClass('in');
            jQuery(tab_id).removeClass('active');

            // active global configuration tab
            jQuery('.tab-settings-0').addClass('tab active');
            tab_id = jQuery('.tab-settings-0 a').attr('href');
            jQuery(tab_id).addClass('in');
            jQuery(tab_id).addClass('active');
        }
    });
});
