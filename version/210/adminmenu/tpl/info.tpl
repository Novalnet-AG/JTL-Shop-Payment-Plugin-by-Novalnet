<table class="info">
<tr>
  <td align="center" valign="top" width="10%">
    <a href="https://www.novalnet.de/" target="_blank">
      <img src="{$url_path}" alt="Novalnet AG" title="Novalnet AG" title="Novalnet AG" />
        </a>
    </td>
    <td>
      Als ein f&uuml;hrendes Unternehmen im Bereich Bezahldienstleistungen, ist es unser Ziel, unseren Kunden die beste und passendste Unterst&uuml;tzung zu bieten, sowohl f&uuml;r Technik und Verkauf als auch f&uuml;r die optimale Sicherheit Ihrer Daten. Unsere Payment-L&ouml;sungen sind so eingerichtet, dass sie im "Click-and-Go-Modus" voll in Ihren Shop integriert werden k&ouml;nnen. Unsere integrierten Payment-L&ouml;sungen sind benutzerfreundlich und lassen sich einfach in jedem Webshop oder in ein selbst entwickelten System integrieren. Auf diesem Weg bieten wir Ihnen viele einfache Optionen, unser System zu integrieren und dabei Zeit und Geld im technischen Bereich zu sparen. <br /><br />
      Falls Sie weitere Informationen ben&ouml;tigen, k&ouml;nnen Sie unser Online-Portal f&uuml;r Endkunden rund <a href="https://card.novalnet.de/">https://card.novalnet.de/</a> um die Uhr besuchen oder unser Verkaufsteam kontaktieren. <b><a href="mailto:sales@novalnet.de">sales@novalnet.de</a></b>
    </td>
  </tr>
  <tr>
  <td align="center" valign="top" width="10%"></td>
  <td>
      Bitte konfigurieren Sie die Zahlungsarten &uuml;ber <a href="{$URL_SHOP}/admin/zahlungsarten.php">Kaufabwicklung -> Zahlungsarten</a> vollst&auml;ndig und f&uuml;gen Sie sie &uuml;ber <a href="{$URL_SHOP}/admin/versandarten.php">Kaufabwicklung -> Versandarten</a> zu den gew&uuml;nschten Versandarten hinzu!
  </td>
  </tr>
</table>
<script>
  // <!--
  {literal}
    $(document).ready(function() {
    var ishttps=document.location.protocol;
    if(ishttps == 'http:')
        $('#content > div.block').prepend('<a href="https://www.novalnet.de" target="_blank"><img style="float: right;" src="http://www.novalnet.de/img/NN_Logo_T.png" title="Novalnet AG" alt="Novalnet AG"/></a>');
     else
        $('#content > div.block').prepend('<a href="https://www.novalnet.de" target="_blank"><img style="float: right;" src="https://www.novalnet.de/img/NN_Logo_T.png" title="Novalnet AG" alt="Novalnet AG"/></a>');
    });
  {/literal}
// -->
</script>
