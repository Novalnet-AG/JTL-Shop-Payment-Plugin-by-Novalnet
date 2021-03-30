/*
 * Novalnet complete Order restriction script
 * By Novalnet (https://www.novalnet.de)
 * Copyright (c) Novalnet
*/

jQuery(document).ready(function () {
    jQuery('#complete_order').submit(function(e){
        jQuery('input[type=submit]').addClass("disabled");
    });
});
