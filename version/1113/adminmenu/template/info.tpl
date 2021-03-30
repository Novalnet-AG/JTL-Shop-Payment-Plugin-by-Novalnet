{**
 * Novalnet admin info template
 * By Novalnet (https://www.novalnet.de)
 * Copyright (c) Novalnet
 *}

<table>
    <script type='text/javascript' src='{$oPlugin->cAdminmenuPfadURL}js/novalnet_admin.js'></script>
    <script type='text/javascript' src='{$oPlugin->cAdminmenuPfadURL}js/{$shopVersion}/novalnet_validation.js'></script>

    <div id='nn_loader' style='display:none'>
       {literal}
            <style type='text/css'>
                #nn_loader {
                    position  : fixed;
                    left      : 0px;
                    top       : 0px;
                    width     : 100%;
                    height    : 100%;
                    z-index   : 9999;
                    background: url('{/literal}{$pluginUrl}{literal}paymentmethod/img/novalnet_loading.gif') 50% 50% no-repeat;
                }
            </style>
        {/literal}
    </div>

    <input type='hidden' id='system_ip' value='{$systemIp}'>
    <input type='hidden' id='remote_ip' value='{$remoteIp}'>
    <input type='hidden' id='nn_callback_url' value='{$callbackUrl}'>
    <input type='hidden' id='nn_admin_url' value='{$oPlugin->cAdminmenuPfadURL}'>
    <tr>
        <td align='center' valign='top'></td>
        <td>
            <label class='nn_map_header'>Um zus&auml;tzliche Einstellungen vorzunehmen, loggen Sie sich in das <a href='https://admin.novalnet.de/' style='font-weight: bold; color:#fff;cursor:pointer;' target='_new'>Novalnet-Administrationsportal</a> ein. Um sich in das Portal einzuloggen, ben&ouml;tigen Sie einen Account bei Novalnet. Falls Sie diesen noch nicht haben, kontaktieren Sie bitte <a href='mailto:sales@novalnet.de' style='font-weight: bold; color:#fff;cursor:pointer;'>sales@novalnet.de</a> (Tel: +49 (089) 923068320)<br/><br/>
            Um die Zahlungsart PayPal zu verwenden, geben Sie bitte Ihre PayPal-API-Daten in das <a href='https://admin.novalnet.de/' style='font-weight: bold; color:#fff;cursor:pointer;' target='_new'>Novalnet-H&auml;ndleradministrationsportal</a> ein
        </td>
    </tr>
    <tr>
        <td align='center' valign='top'></td>
        <td>
          Als ein f&uuml;hrendes Unternehmen im Bereich Bezahldienstleistungen, ist es unser Ziel, unseren Kunden die beste und passendste Unterst&uuml;tzung zu bieten, sowohl f&uuml;r Technik und Verkauf als auch f&uuml;r die optimale Sicherheit Ihrer Daten. Unsere Payment-L&ouml;sungen sind so eingerichtet, dass sie im "Click-and-Go-Modus" voll in Ihren Shop integriert werden k&ouml;nnen. Unsere integrierten Payment-L&ouml;sungen sind benutzerfreundlich und lassen sich einfach in jedem Webshop oder in ein selbst entwickelten System integrieren. Auf diesem Weg bieten wir Ihnen viele einfache Optionen, unser System zu integrieren und dabei Zeit und Geld im technischen Bereich zu sparen. <br /><br />
          Falls Sie weitere Informationen ben&ouml;tigen, k&ouml;nnen Sie unser Online-Portal f&uuml;r Endkunden unter <a href='https://card.novalnet.de/'>https://card.novalnet.de/</a> rund um die Uhr besuchen oder unser Verkaufsteam <b><a href='mailto:sales@novalnet.de'>(sales@novalnet.de)</a></b> kontaktieren.
        </td>
    </tr>

    <tr>
        <td align='center' valign='top'></td>
        <td>
            Bitte konfigurieren Sie die Zahlungsarten &uuml;ber <a href='{$shopUrl}/admin/zahlungsarten.php'>{if $shopVersion == '4x'}Storefront -> Zahlungsarten -> &Uuml;bersicht{else}Kaufabwicklung -> Zahlungsarten{/if}</a> vollst&auml;ndig und f&uuml;gen Sie diese &uuml;ber <a href='{$shopUrl}/admin/versandarten.php'>{if $shopVersion == '4x'}Storefront -> Kaufabwicklung -> Versandarten{else}Kaufabwicklung -> Versandarten{/if}</a> zu den gew&uuml;nschten Versandarten hinzu!
        </td>
    </tr>
</table>
