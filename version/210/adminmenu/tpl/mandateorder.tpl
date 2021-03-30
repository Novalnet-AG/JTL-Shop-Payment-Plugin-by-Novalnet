<div id="content">
   {if isset($cHinweis) && $cHinweis|count_characters > 0}
      <p class="box_success">{$cHinweis}</p>
   {/if}
  <p class="box_error" id ='box_error'>Bitte w&auml;hlen Sie zum Einreichen des Mandats Datum in Novalnet</p>
  {if $oBestellung_arr|@count > 0 && $oBestellung_arr}

      <div class="category">Bestellungen</div>

      <form name="novalnetbestellungen" method="post" action="novalnetmandateconfirm.php">
        <input type="hidden" name="{$session_name}" value="{$session_id}" />
        <input type="hidden" name="zuruecksetzen" value="1" />

        {if isset($cSuche) && $cSuche|count_characters > 0}
           <input type="hidden" name="cSuche" value="{$cSuche}" />
        {/if}

        <table class="list">
           <thead>
              <tr>
                <th></th>
                <th class="tleft">Bestellnummer</th>
                <th class="tleft">Kunde</th>
                <th class="tleft">Versandart</th>
                <th class="tleft">Zahlungsart</th>
                <th>Abgeholt durch Wawi   </th>
                <th>Warensumme</th>
                <th class="tcenter">Bestelldatum</th>
              </tr>
           </thead>
           <tbody>
              {foreach name=bestellungen from=$oBestellung_arr item=oBestellung} 
              {if $oBestellung->kZahlungsart == $novalnetsepa && $oBestellung->cStatus == '1'}
                <tr class="tab_bg{$smarty.foreach.bestellungen.iteration%2}">

                   <td class="check">
                    <input type='radio' name='mandateorderoption' class='mandateorderoption' value = '{$oBestellung->cBestellNr}'>
                   </td>
                   <td>{$oBestellung->cBestellNr}</td>
                   <td>{if $oBestellung->oKunde->cVorname || $oBestellung->oKunde->cNachname || $oBestellung->oKunde->cFirma}{$oBestellung->oKunde->cVorname} {$oBestellung->oKunde->cNachname}{if isset($oBestellung->oKunde->cFirma) && $oBestellung->oKunde->cFirma|count_characters > 0} ({$oBestellung->oKunde->cFirma}){/if}{else}{#noAccount#}{/if}</td>
                   <td>{$oBestellung->cVersandartName}</td>
                   <td>{$oBestellung->cZahlungsartName}</td>
                   <td class="tcenter">{if $oBestellung->cAbgeholt == "Y"}{#yes#}{else}{#no#}{/if}</td>
                   <td class="tcenter">{$oBestellung->WarensummeLocalized[0]}</td>
                   <td class="tright">{$oBestellung->dErstelldatum_de}</td>
                   <input type='hidden' name='mandatecISO' id='mandatecISO' value = '{$oBestellung->Waehrung->cISO}'>
                </tr>
                {/if}
              {/foreach}
           </tbody>
        </table>
        <div class="save_wrapper">
            <input type='text' name='mandate_date' id='mandate_date' placeholder='DD-MM-YYYY' style='float:left'>
           <button name="mandateconfirm" type="button" class="button orange" style ='margin-left:-868px;' id='mandateconfirm'>Mandat best&auml;tigung </button>
           <input type="hidden" name="mandateconfirmorder" id='mandateconfirmorder' value="" />
        </div>
      </form>
   {/if}
</div>
{literal}
  <script type="text/javascript">
    $(document).ready(function() {

     $('.mandateorderoption').click(function() {
      $('#mandateconfirmorder').attr('value', $(this).val());
    });
    $('#mandateconfirm').click(function(e) {
      var datemandate = $('#mandate_date').val();
      var mandateorder =  $('#mandateconfirmorder').val();
      var mandatecISO  =  $('#mandatecISO').val();
       $.post(
                'novalnetmandateconfirm.php',
                {
                       'mandate_date': datemandate,
                       'mandate_order': mandateorder,
                       'mandate_ciso' : mandatecISO,
                },
                function(data)
                { if(data.result == 100) {
						window.location.reload();
						alert(data.error_msg);
					}
					else
						$('#box_error').text(data.error_msg);
                },'json'
                );

    });
    });
  </script>
{/literal}
