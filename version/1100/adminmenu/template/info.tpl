<table class='list table'>
	<script type='text/javascript' src='{$oPlugin->cAdminmenuPfadURL}js/novalnet_admin.js'></script>

	<div id="nn_loader" style="display:none">
		<style type="text/css">
			{literal}
				#nn_loader {
					position  : fixed;
					left	  : 0px;
					top		  : 0px;
					width	  : 100%;
					height	  : 100%;
					z-index	  : 9999;
					background: url('{/literal}{$pluginUrl}{literal}paymentmethod/img/novalnet_loading.gif') 50% 50% no-repeat;
				}
			{/literal}
		</style>
	</div>
	
	<input type='hidden' id='systemIp' value='{$systemIp}' ></script>
	<input type="hidden" id="nn_callback_url" value="{$callbackUrl}">
	<input type="hidden" id="nn_lib_url" value="{$pluginUrl}lib/">
	<input type="hidden" id="nn_plugin_url" value="{$pluginUrl}adminmenu/">
	<tr class='tab_bg1'>
		<td align='center' valign='top'></td>
		<td>
		  Als ein f&uuml;hrendes Unternehmen im Bereich Bezahldienstleistungen, ist es unser Ziel, unseren Kunden die beste und passendste Unterst&uuml;tzung zu bieten, sowohl f&uuml;r Technik und Verkauf als auch f&uuml;r die optimale Sicherheit Ihrer Daten. Unsere Payment-L&ouml;sungen sind so eingerichtet, dass sie im "Click-and-Go-Modus" voll in Ihren Shop integriert werden k&ouml;nnen. Unsere integrierten Payment-L&ouml;sungen sind benutzerfreundlich und lassen sich einfach in jedem Webshop oder in ein selbst entwickelten System integrieren. Auf diesem Weg bieten wir Ihnen viele einfache Optionen, unser System zu integrieren und dabei Zeit und Geld im technischen Bereich zu sparen. <br /><br />
		  Falls Sie weitere Informationen ben&ouml;tigen, k&ouml;nnen Sie unser Online-Portal f&uuml;r Endkunden unter <a href='https://card.novalnet.de/'>https://card.novalnet.de/</a> rund um die Uhr besuchen oder unser Verkaufsteam <b><a href='mailto:sales@novalnet.de'>(sales@novalnet.de)</a></b> kontaktieren.
		</td>
	</tr>
	
	<tr class='tab_bg1'>
		<td align='center' valign='top'></td>
		<td>
			Bitte konfigurieren Sie die Zahlungsarten &uuml;ber <a href='{$shopUrl}/admin/zahlungsarten.php'>Storefront -> Zahlungsarten -> &Uuml;bersicht</a> vollst&auml;ndig und f&uuml;gen Sie diese &uuml;ber <a href='{$shopUrl}/admin/versandarten.php'>Storefront -> Kaufabwicklung -> Versandarten</a> zu den gew&uuml;nschten Versandarten hinzu!
		</td>
	</tr>
</table>
