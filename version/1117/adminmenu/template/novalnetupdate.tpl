{**
 * Novalnet admin info template
 * By Novalnet (https://www.novalnet.de)
 * Copyright (c) Novalnet
 *}

<table>
    <script type='text/javascript' src='{$oPlugin->cAdminmenuPfadURL}js/novalnet_update.js'></script>

    <tr>
        <td><img src="{$pluginUrl}/adminmenu/images/novalnet.png"></td>
        <td></td>
    </tr>
    <tr>
        <td><h1>Novalnet-Zahlungsplugin V11.1.7</h1><span>Vielen Dank, dass Sie die neueste Version des Novalnet Zahlungs-Plugins installiert haben. Diese Version bringt einige gro&szlig;artige neue Funktionen und Erweiterungen. Hoffentlich macht es Ihnen Spa&szlig;, damit zu arbeiten!</span></td>
        <td></td>
    </tr>
    <tr>
        <td><a href="#" style="font-weight:bold;" class="nn_global_configuration"><br/>Zur Novalnet Haupteinstellungen gehen</a><br/><h3>Sehen Sie, was es neues gibt!</h3></td>
        <td></td>
    </tr>
    <hr/>
</table>
<table>
    <tr>
        <td><img src="{$pluginUrl}/adminmenu/images/projects_tab.png" width="600" height="150"></td>
        <td style="padding:50px;"></td>
        <td>
            <h3>Aktivierungsschl&uuml;ssel des Produkts</h3>
            <p>Novalnet hat den Aktivierungsschl&uuml;ssel f&uuml;r Produkte eingef&uuml;hrt, um die gesamten H&auml;ndler-Zugangsdaten automatisch einzutragen, wenn dieser Schl&uuml;ssel in die Novalnet-Hauptkonfiguration eingetragen wird.</p>
        </td>
    </tr>
    <tr>
        <td><br/><img src="{$pluginUrl}/adminmenu/images/product_activation_key.png" width="600" height="150"></td>
        <td style="padding:50px;"></td>
        <td><br/>
            <p>Um diesen Aktivierungschl&uuml;ssel f&uuml;r das Produkt zu erhalten, gehen Sie zum <a href="https://admin.novalnet.de/" target="_blank">Novalnet-Admin-Portal</a> - <b>Projekte:</b> Informationen zum jeweiligen Projekt - <b>Parameter Ihres Shops: API Signature (Aktivierungsschl&uuml;ssel des Produkts)</b></p>
        </td>
    </tr>
</table>
<hr/>
<table>
    <tr>
        <td>
            <span style="font-weight:bold;">Einstellung der IP-Adresse</span><br/>
            <p>F&uuml;r alle Zugriffe auf die API (automatische Konfiguration mit dem Aktivierungsschl&uuml;ssel des Produkts, Laden eines Kreditkarten-iFrame, Zugriff auf die API f&uuml;r die &Uuml;bermittlung von Transaktionen, die Abfrage des Transaktionsstatus und &Auml;nderungen an Transaktionen), muss eine IP-Adresse f&uuml;r den Server im Novalnet-Administrationsportal eingerichtet sein.</p>
        </td>
        <td style="padding:50px;"></td>
        <td><img src="{$pluginUrl}/adminmenu/images/projects_tab.png" width="600" height="150"></td>
    </tr>
    <tr>
        <td><br/>
            <span>Um eine IP-Adresse einzurichten, gehen Sie im <a href="https://admin.novalnet.de/" target="_blank">Novalnet-Admin-Portal</a> zu <b>Projekte:</b> Informationen zum jeweiligen Projekt - <b>Projekt&uuml;bersicht: IPs f&uuml;r Zahlungsaufrufe.</b></span>
        </td>
        <td style="padding:50px;"></td>
        <td><br/><img src="{$pluginUrl}/adminmenu/images/system_ip_configuration.png" width="600" height="80"></td>
    </tr>
</table>
<hr/>
<table>
    <tr>
        <td><img src="{$pluginUrl}/adminmenu/images/projects_tab.png" width="600" height="150"></td>
        <td style="padding:50px;"></td>
        <td>
            <span style="font-weight:bold;">Aktualisierung der H&auml;ndlerskript-URL</span><br/>
            <p>Die H&auml;ndlerskript-URL wird dazu ben&ouml;tigt, um den Transaktionsstatus in der Datenbank / im System des H&auml;ndlers aktuell und auf demselben Stand wie bei Novalnet zu halten. Dazu muss die H&auml;ndlerskript-URL im Novalnet-H&auml;ndleradministrationsportal eingerichtet werden. <br/><br/> Vom Novalnet-Server wird die Information zu jeder Transaktion und deren Status (durch asynchrone Aufrufe) an den Server des H&auml;ndlers &uuml;bertragen.</p>
        </td>
    </tr>
    <tr>
        <td><br/><img src="{$pluginUrl}/adminmenu/images/vendor_script_configuration.png" width="600" height="80"></td>
        <td style="padding:50px;"></td>
        <td><br/><span>Um den H&auml;ndlerskript-URL einzurichten, gehen Sie im <a href="https://admin.novalnet.de/" target="_blank">Novalnet-Admin-Portal</a> zu  <b>Projekte:</b> Informationen zum jeweiligen Projekt - <b>Projekt&uuml;bersicht: H&auml;ndlerskript-URL</b></span></td>
    </tr>
</table>
<hr/>
<table>
    <tr>
        <td>
            <span><b>PAYPAL</b></span>
            <p>Um PayPal-Zahlungen verarbeiten zu k&ouml;nnen, m&uuml;ssen Sie Ihre PayPal-API-Details im Novalnet-Adminstrationsportal konfigurieren.</p>
        </td>
        <td style="padding:50px;"></td>
        <td><img src="{$pluginUrl}/adminmenu/images/paypal_config_home.png" width="450" height="180"></td>
    </tr>
    <tr>
        <td><br/>
            <p>Um die PayPal-API-Details zu konfigurieren, gehen Sie bitte im <a href="https://admin.novalnet.de/" target="_blank">Novalnet-Admin-Portal</a> zu <b>Projekte:</b> [Informationen zum jeweiligen Projekt] - <b>Zahlungsmethoden : PayPal - Konfigurieren.</b></p>
        </td>
        <td style="padding:50px;"></td>
        <td><br/><img src="{$pluginUrl}/adminmenu/images/paypal_config.png" width="450" height="150"></td>
    </tr>
</table>

<table>
    <tr>
        <td><br/><br/><h3>Moment, es gibt noch mehr!</h3></td>
    </tr>
</table>
<hr/>
<div class='nn_row'>
    <div class='nn_column'>
        <span><b>Shopping mit einem Klick</b></span>
        <p>
            M&ouml;chten Sie Ihre Kunden eine Bestellung mit einem einzigen Klick aufgeben lassen?<br/>
            Mit dem Novalnet-Zahlungsplugin ist dies m&ouml;glich! Dieses Merkmal erm&ouml;glicht es dem Endkunden, bequemer mit hinterlegten Konto-/Kartendaten zu bezahlen.
       </p>
    </div>
    <div class='nn_column'>
        <span><b>Beschleunigter Kreditkarten-iFrame</b></span>
        <p>
            Jetzt haben wir den iFrame f&uuml;r Kreditkartenzahlungen mit den dynamischsten Funktionen aktualisiert. Mit nur wenig Code haben wir den Inhalt des Kreditkarten-iFrame beschleunigt und nutzerfreundlicher gemacht.<br/>
            Der H&auml;ndler kann selbst die CSS-Einstellungen des Kreditkarten-iFrame-Formulars anpassen.
        </p>
    </div>
    <div class='nn_column'>
        <span><b>Buchung mit Betrag 0</b></span>
        <p>
            Die Funktion "Buchung mit Betrag 0" erm&ouml;glicht es dem H&auml;ndler, ein Produkt zu unterschiedlichen Preisen im Shop zu verkaufen. Die Bestellung wird zuerst mit dem Betrag 0 verarbeitet, danach kann der H&auml;ndler sp&auml;ter den Bestellbetrag abbuchen, um die Transaktion abzuschlie&szlig;en.
        </p>
    </div>
</div>

<hr/>
<table>
    <tr>
        <td><a href="#" style="font-weight:bold;" class="nn_global_configuration"><br/>Zur Novalnet Haupteinstellungen gehen</a><br/></td>
    </tr>
</table>
