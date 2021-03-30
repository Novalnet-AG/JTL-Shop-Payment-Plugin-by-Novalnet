<link rel='stylesheet' type='text/css' href="{$ordersPathDir}css/novalnet_admin.css">
<label class="nn_map_header">
 Loggen Sie sich hier mit Ihren Novalnet H&auml;ndler-Zugangsdaten ein. Um neue Zahlungsarten zu aktivieren, kontaktieren Sie bitte <a href="mailto:support@novalnet.de" style="font-weight: bold; color:#fff;cursor:pointer;">support@novalnet.de</a>
 </label>
<iframe id="nn_iframe" frameborder="0"></iframe>
<script>
{literal}
    $(document).ready(function() {
		$('#nn_iframe').attr('src','https://admin.novalnet.de');
        $('#nn_iframe').css({height:$(window).height(),width:$(window).width()});
    });
{/literal}
</script>
