<table class='info' width='100%'>
    <script type='text/javascript' src='{$oPlugin->cAdminmenuPfadURL}js/novalnet_admin.js' ></script>
    <input type="hidden" id="nn_callback_url" value="{$callback_url}">
    <input type="hidden" id="nn_lib_url" value="{$pluginUrl}lib/">
    <input type="hidden" id="nn_plugin_url" value="{$pluginUrl}adminmenu/">
    <tr>
        <td align="center" valign="top" width="10%">
            <a href="https://www.novalnet.de/" target="_blank">
                <img src="{$url_path}" alt="Novalnet" title="Novalnet" title="Novalnet" />
            </a>
        </td>
        <td>
        Als ein f&uuml;hrendes Unternehmen im Bereich Bezahldienstleistungen, ist es unser Ziel, unseren Kunden die beste und passendste Unterst&uuml;tzung zu bieten, sowohl f&uuml;r Technik und Verkauf als auch f&uuml;r die optimale Sicherheit Ihrer Daten. Unsere Payment-L&ouml;sungen sind so eingerichtet, dass sie im "Click-and-Go-Modus" voll in Ihren Shop integriert werden k&ouml;nnen. Unsere integrierten Payment-L&ouml;sungen sind benutzerfreundlich und lassen sich einfach in jedem Webshop oder in ein selbst entwickelten System integrieren. Auf diesem Weg bieten wir Ihnen viele einfache Optionen, unser System zu integrieren und dabei Zeit und Geld im technischen Bereich zu sparen. <br /><br />
        Falls Sie weitere Informationen ben&ouml;tigen, k&ouml;nnen Sie unser Online-Portal f&uuml;r Endkunden unter <a href="https://card.novalnet.de/">https://card.novalnet.de/</a> rund um die Uhr besuchen oder unser Verkaufsteam <b><a href="mailto:sales@novalnet.de">(sales@novalnet.de)</a></b> kontaktieren.
        </td>
    </tr>
    <tr>
        <td align="center" valign="top" width="10%"></td>
        <td>
            Bitte konfigurieren Sie die Zahlungsarten &uuml;ber <a href="{$NN_URL_PATH}/admin/zahlungsarten.php">Kaufabwicklung -> Zahlungsarten</a> vollst&auml;ndig und f&uuml;gen Sie diese &uuml;ber <a href="{$NN_URL_PATH}/admin/versandarten.php">Kaufabwicklung -> Versandarten</a> zu den gew&uuml;nschten Versandarten hinzu!
        </td>
    </tr>
</table>
<script>
  // <!--
  {literal}
    $(document).ready(function() {
        $('#content > div.block').prepend('<a href="https://www.novalnet.de" target="_blank"><img style="float: right;" src={/literal}"{$url_path}"{literal} title="Novalnet" alt="Novalnet"/></a>');
    });
  {/literal}
// -->
</script>
