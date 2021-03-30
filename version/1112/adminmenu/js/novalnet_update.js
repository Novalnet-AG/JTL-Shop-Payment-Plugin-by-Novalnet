/*
 * Novalnet admin update page script
 * By Novalnet (https://www.novalnet.de)
 * Copyright (c) Novalnet
*/

jQuery(document).ready(function(evt)
{
    jQuery('.nn_global_configuration').click(function(evt) {
        evt.preventDefault();
        if (jQuery('.nn_global_configuration').closest('div').parent('div').siblings().find( "li" ).last().attr('class') == 'tab-novalnetupdate tab-info tab active' || jQuery('.nn_global_configuration').closest('div').parent('div').siblings().find( "li" ).last().attr('class') == 'tab-novalnetupdate tab active') {
            jQuery('.nn_global_configuration').closest('div').parent('div').siblings().find( "li" ).last().removeClass('tab-novalnetupdate tab-info tab active');
            jQuery('.nn_global_configuration').closest('div').parent('div').siblings().find( "li" ).last().addClass('tab-novalnetupdate tab-info tab');
            jQuery('.tab-settings-0').addClass('tab active');
            jQuery('.nn_global_configuration').closest('div').removeClass('active');
            jQuery('.nn_global_configuration').closest('div').removeClass('in');
            tab_id = jQuery('.tab-settings-0 a').attr('href').replace('#', '');
            jQuery('.nn_global_configuration').closest('div').siblings().each(function() {
                if (jQuery(this).attr('id') == tab_id) {
                    jQuery(this).addClass('active');
                    jQuery(this).addClass('in');
                }
            });
        }
    });
});
