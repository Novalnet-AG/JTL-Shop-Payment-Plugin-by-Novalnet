{**
 * Novalnet admin orders template
 * By Novalnet (https://www.novalnet.de)
 * Copyright (c) Novalnet
 *}

{if $oBestellung_arr|@count > 0 && $oBestellung_arr}
    <div class="block clearall">
        <div class="left">
            {if $oBlaetterNaviUebersicht->nAktiv == 1}
                <div class="pages tright">
                        <span class="pageinfo">{#page#}: <strong>{$oBlaetterNaviUebersicht->nVon}</strong> - {$oBlaetterNaviUebersicht->nBis} {#from#} {$oBlaetterNaviUebersicht->nAnzahl}</span>
                        <a class="back" href="plugin.php?kPlugin={$oPlugin->kPlugin}&cPluginTab=Novalnet-Bestellungen&s1={$oBlaetterNaviUebersicht->nVoherige}{if isset($cSuche) && $cSuche|count_characters > 0}&cSuche={$cSuche}{/if}">&laquo;</a>
                        {if $oBlaetterNaviUebersicht->nAnfang != 0}<a href="plugin.php?kPlugin={$oPlugin->kPlugin}&cPluginTab=Novalnet-Bestellungen&s1={$oBlaetterNaviUebersicht->nAnfang}{if isset($cSuche) && $cSuche|count_characters > 0}&cSuche={$cSuche}{/if}">{$oBlaetterNaviUebersicht->nAnfang}</a> ... {/if}
                            {foreach name=blaetternavi from=$oBlaetterNaviUebersicht->nBlaetterAnzahl_arr item=Blatt}
                                <a class="page {if $oBlaetterNaviUebersicht->nAktuelleSeite == $Blatt}active{/if}" href="plugin.php?kPlugin={$oPlugin->kPlugin}&cPluginTab=Novalnet-Bestellungen&s1={$Blatt}{if isset($cSuche) && $cSuche|count_characters > 0}&cSuche={$cSuche}{/if}">{$Blatt}</a>
                            {/foreach}

                        {if $oBlaetterNaviUebersicht->nEnde != 0}
                            ... <a class="page" href="plugin.php?kPlugin={$oPlugin->kPlugin}&cPluginTab=Novalnet-Bestellungen&s1={$oBlaetterNaviUebersicht->nEnde}{if isset($cSuche) && $cSuche|count_characters > 0}&cSuche={$cSuche}{/if}">{$oBlaetterNaviUebersicht->nEnde}</a>
                        {/if}
                        <a class="next" href="plugin.php?kPlugin={$oPlugin->kPlugin}&cPluginTab=Novalnet-Bestellungen&s1={$oBlaetterNaviUebersicht->nNaechste}{if isset($cSuche) && $cSuche|count_characters > 0}&cSuche={$cSuche}{/if}">&raquo;</a>
                </div>
            {/if}
        </div>
    </div>
    <div class="category">Bestellungen</div>
        <table class="list">
            <thead>
                <tr>
                    <th>Bestellnummer</th>
                    <th class="tleft">Kunde</th>
                    <th class="tleft">Zahlungsart</th>
                    <th class="tleft">Status</th>
                    <th>Abgeholt durch Wawi</th>
                    <th>Warensumme</th>
                    <th class="tcenter">Bestelldatum</th>
                </tr>
            </thead>
            <tbody>
                {foreach name=bestellungen from=$oBestellung_arr item=oBestellung}
                    <tr class="tab_bg{$smarty.foreach.bestellungen.iteration%2}">
                        {assign var = bestellen value = $oBestellung->cBestellNr}
                        <td style="cursor:pointer;text-decoration:underline" class="tcenter" onclick="adminOrderDisplay('{$bestellen}') ;">{$oBestellung->cBestellNr}</td>
                        <td>{if $oBestellung->oKunde->cVorname || $oBestellung->oKunde->cNachname || $oBestellung->oKunde->cFirma}{$oBestellung->oKunde->cVorname} {$oBestellung->oKunde->cNachname}{if isset($oBestellung->oKunde->cFirma) && $oBestellung->oKunde->cFirma|count_characters > 0} ({$oBestellung->oKunde->cFirma}){/if}{else}- Kein Kundenkonto -{/if}</td>
                        <td>{$oBestellung->cZahlungsartName}</td>
                        {assign var = status value = $oBestellung->cStatus|string_format:"%d"}
                        <td>{$oBestellung_status.$status}</td>
                        <td class="tcenter">{if $oBestellung->cAbgeholt == "Y"}{#yes#}{else}{#no#}{/if}</td>
                        <td class="tcenter">{$oBestellung->WarensummeLocalized[0]}</td>
                        <td class="tright">{$oBestellung->dErstelldatum_de}</td>
                        <input type="hidden" name="nn_order_no" id="nn_order_no" value="{$oBestellung->cBestellNr}">
                        <input type="hidden" name="nn_plugin_include" id="nn_plugin_include" value="{$pluginInclude}">
                    </tr>
                {/foreach}
            </tbody>
        </table>
{else}
	<p class="box_info"> Keine Daten vorhanden.</p>
{/if}

<link rel='stylesheet' type='text/css' href="{$adminPathDir}css/novalnet_admin.css">
<div class="adminCover">&nbsp;</div>
{foreach name=bestellungen from=$oBestellung_arr item=oBestellung}
    <div id='admin_order_display_block' style='display:none;' class='overlay_window_block'></div>
{/foreach}
