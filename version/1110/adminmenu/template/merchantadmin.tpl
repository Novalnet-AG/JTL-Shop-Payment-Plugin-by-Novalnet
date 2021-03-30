{**
 * Novalnet merchant admin template
 * By Novalnet AG (https://www.novalnet.de)
 * Copyright (c) Novalnet AG
 *}

<link rel='stylesheet' type='text/css' href='{$adminPathDir}css/novalnet_admin.css'>
    <label class="nn_map_header">
    Loggen Sie sich hier mit Ihren Novalnet H&auml;ndler-Zugangsdaten ein. Um neue Zahlungsarten zu aktivieren, kontaktieren Sie bitte <a href="mailto:support@novalnet.de" style="font-weight: bold; color:#fff;cursor:pointer;">support@novalnet.de</a>
    </label>
<iframe id='nn_iframe' frameborder='0'></iframe>
<script>
    {literal}
        $(document).ready(function() {
            $('#nn_iframe').attr('src','https://admin.novalnet.de').css({height:$(window).height(),width:'100%'});
        });
    {/literal}
</script>
