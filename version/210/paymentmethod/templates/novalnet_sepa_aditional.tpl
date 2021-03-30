<div class="container form">
  <fieldset>
    <legend>{$lang_payment_name}</legend>
     {if $error}
      {if $error_desc}
      <p class="box_error">{$error_desc}</p>
      {else}
      <p class="box_error">{$lang_field_validation_message}</p>
      {/if}
    {/if}
    {if $sepaerror}
        <p style="color:red;">{$basic_error_msg}</p>
    {else}
    <table style="width:100%; border:2">
      <tr>
         <td valign="top">
        <div class="debit">
         <p class="none">
        <input type="hidden" id="sepa_field_validator"  name="sepa_field_validator" value="" />
        <input type="hidden" id="original_sepa_customstyle_css" value="" />
        <input type="hidden" id="original_sepa_customstyle_cssval" value="" />
        <input id="sepa_owner" name="sepa_owner" type="hidden" value="">
        <input id="sepa_unique_id" name="sepa_unique_id" type="hidden" value="">
        <input id="sepa_pan_hash" name="sepa_pan_hash" type="hidden" value="">
        <input id="sepa_mandate_ref" name="sepa_mandate_ref" type="hidden" value="">
        <input id="sepa_mandate_date" name="sepa_mandate_date" type="hidden" value="">
        <input id="sepa_iban_conformed" type="hidden" name="sepa_iban_conformed" value="" />
           
          <span id="loading"><img src="{$novalnet_protocol}www.novalnet.de/img/novalnet-loading-icon.gif" alt="Novalnet AG" /></span>
        <div> <iframe id="payment_form_novalnetSepa" name="payment_form_novalnetSepa" width="100%" height="430" src="{$path}" onload="doHideLoadingImageAndDisplayIframe(this)" frameBorder="0" scrolling="no"></iframe></div>
          {literal}
          <script type='text/javascript'>
          function doHideLoadingImageAndDisplayIframe(element) { 
            document.getElementById("loading").style.display="none";
            var iframe = (element.contentWindow || element.contentDocument);
            if (iframe.document) iframe=iframe.document;
          }

		    var novalnetSepaHiddenId = "novalnet_Sepa_id";
		    var getSepaInputForm 	 = document.getElementById("sepa_pan_hash");

		    if(getSepaInputForm.form.getAttribute("id")==null || getSepaInputForm.form.getAttribute("id") =="") {
			    getSepaInputForm.form.setAttribute("id", novalnetSepaHiddenId);
			    getSepaFormId = getSepaInputForm.form.getAttribute("id");
		    }else{
			    getSepaFormId = getSepaInputForm.form.getAttribute("id");
		    }
		  document.forms[getSepaFormId].onsubmit = function () { 
			  var ifr_sepa = document.getElementById("payment_form_novalnetSepa");
			  var sepaIframe = (ifr_sepa.contentWindow || ifr_sepa.contentDocument);
			  if (sepaIframe.document)
		    { 
				    sepaIframe=sepaIframe.document;
				    var sepa_owner=0; var sepa_accountno=0; var sepa_bankcode=0; var sepa_iban=0; var sepa_swiftbic=0;
				    var sepa_hash=0; var sepa_country=0;
				
				if(sepaIframe.getElementById("novalnet_sepa_owner").value!= '') sepa_owner=1;
				if(sepaIframe.getElementById("novalnet_sepa_accountno").value!= '') sepa_accountno=1;
				if(sepaIframe.getElementById("novalnet_sepa_bankcode").value!= '') sepa_bankcode=1;
				if(sepaIframe.getElementById("novalnet_sepa_iban").value!= '') sepa_iban=1;
				if(sepaIframe.getElementById("novalnet_sepa_swiftbic").value!= '') sepa_swiftbic=1;
				if(sepaIframe.getElementById("nnsepa_hash").value!= '') sepa_hash=1;
				if(sepaIframe.getElementById("novalnet_sepa_country").value!= '') {
					var country = sepaIframe.getElementById("novalnet_sepa_country");
					sepa_country = 1+'-'+country.options[country.selectedIndex].value;
				}

				document.getElementById('sepa_field_validator').value = sepa_owner+','+sepa_accountno+','+sepa_bankcode+','+sepa_iban+','+sepa_swiftbic+','+sepa_hash+','+sepa_country;
				
				if( sepaIframe.getElementById("nnsepa_hash").value != null ) {
				 
          document.getElementById('sepa_mandate_ref').value = sepaIframe.getElementById("nnsepa_mandate_ref").value;
          document.getElementById("sepa_mandate_date").value=sepaIframe.getElementById("nnsepa_mandate_date").value;
          document.getElementById("sepa_pan_hash").value = sepaIframe.getElementById("nnsepa_hash").value;
          document.getElementById("sepa_unique_id").value = sepaIframe.getElementById("nnsepa_unique_id").value;
          document.getElementById('sepa_iban_conformed').value = sepaIframe.getElementById("nnsepa_iban_confirmed").value;
          document.getElementById('sepa_owner').value = sepaIframe.getElementById("novalnet_sepa_owner").value;
				}
      }
		}
    </script>
    {/literal} 
        
</p>
</div>
        {$lang_sepa_description}
        {$lang_sepa_test_mode_info}
         </td>
      </tr>
    </table>{/if}
  </fieldset>
</div>
