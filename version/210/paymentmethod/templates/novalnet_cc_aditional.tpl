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
    {if $ccerror}
        <p style="color:red;">{$basic_error_msg}</p>
    {else}
    <table style="width:100%; border:2">
      <tr>
         <td valign="top">
         <div class="debit">
         <p class="none"> 
          <input id="original_vendor_id" type="hidden" value="{$vendor_id}">
          <input id="original_vendor_authcode" type="hidden" value="{$auth_code}">
          <input id="cc_fldvalidator" type="hidden" name="cc_fldvalidator" value="" /> 
          <input id="cc_unique_id" name="cc_unique_id" type="hidden" value="" />
          <input id="cc_pan_hash" name="cc_pan_hash" type="hidden" value="" />
          <input id="cc_type" type="hidden" name="cc_type" value="" />
          <input id="cc_owner" type="hidden" name="cc_owner" value="" />
          <input id="cc_exp_month" type="hidden" name="cc_exp_month" value="" />
          <input id="cc_exp_year" type="hidden" name="cc_exp_year" value="" />
          <input id="cc_cid" type="hidden" name="cc_cid" value="" />
           
          <span id="loading"><img src="{$novalnet_protocol}www.novalnet.de/img/novalnet-loading-icon.gif" alt="Novalnet AG" /></span>
        <div> <iframe id="payment_form_novalnetCc" name="payment_form_novalnetCc" width="100%" height="240" src="novalnet_cc_form.php?lang_code={$lang_code}&vendor_id={$vendor_id}&product_id={$product_id}&auth_code={$auth_code}&nn_hash={$nn_hash}&fldVdr={$fldVdr}&sepapayment_type={$sepapayment_type}&payment_id={$payment_id}&cc_holder={$cc_name}" onload="getFormValue(this)" frameBorder="0" scrolling="no"></iframe></div>
          {literal}
          <script type='text/javascript'>
          function getFormValue(element){
            document.getElementById('loading').style.display = 'none';

            var frameObj =(element.contentWindow || element.contentDocument);
            if (frameObj.document) frameObj=frameObj.document;

            var getInputForm = document.getElementById("original_vendor_id");
            var formid=getInputForm.form.getAttribute("id");

            document.getElementById(formid).onsubmit = function() { 
			
			        var ifr = document.getElementById("payment_form_novalnetCc");
        			var ccIframe = (ifr.contentWindow || ifr.contentDocument);
			        if (ccIframe.document) ccIframe=ccIframe.document;

        			var cc_type=0; var cc_owner=0; var cc_no=0; var cc_hash=0; var cc_month=0; var cc_year=0; var cc_cid=0;
        			if(ccIframe.getElementById("novalnetCc_cc_type").value!= '') cc_type=1;
        			if(ccIframe.getElementById("novalnetCc_cc_owner").value!= '') cc_owner=1;
        			if(ccIframe.getElementById("novalnetCc_cc_number").value!= '') cc_no=1;
        			if(ccIframe.getElementById("novalnetCc_expiration").value!= '') cc_month = 1;
        			if(ccIframe.getElementById("novalnetCc_expiration_yr").value!= '') cc_year = 1;
        			if(ccIframe.getElementById("novalnetCc_cc_cid").value!= '') cc_cid=1;

			        document.getElementById('cc_fldvalidator').value = cc_type+','+cc_owner+','+cc_no+','+cc_month+','+cc_year+','+cc_cid;

              document.getElementById("cc_pan_hash").value = frameObj.getElementById("nncc_cardno_id").value;
              document.getElementById("cc_unique_id").value = frameObj.getElementById("nncc_unique_id").value;
              document.getElementById('cc_type').value  = frameObj.getElementById("novalnetCc_cc_type").value; 
      				document.getElementById('cc_owner').value = frameObj.getElementById("novalnetCc_cc_owner").value;
      				document.getElementById('cc_exp_month').value = frameObj.getElementById("novalnetCc_expiration").value;
      				document.getElementById('cc_exp_year').value  = frameObj.getElementById("novalnetCc_expiration_yr").value;
      				document.getElementById('cc_cid').value = frameObj.getElementById("novalnetCc_cc_cid").value;
      				document.getElementById("nn_hash").value  = frameObj.getElementById("nncc_hash_id").value;
      				document.getElementById("nncc_uniqid").value = frameObj.getElementById("nncc_unique_id").value;
           
            }  
          }

	      var getInputForm = document.getElementById('original_vendor_id');
          if(getInputForm.form.getAttribute("id") == null || getInputForm.form.getAttribute("id") == "") {
      			getInputForm.form.setAttribute("id", novalnetHiddenId);
			     var getFormId = getInputForm.form.getAttribute("id");
		     } else {
      			var getFormId = getInputForm.form.getAttribute("id");
  		  }
  	
          

          </script>
           {/literal}
           
           
          </p>
        </div>
        {$lang_cc_description}
        {$lang_cc_test_mode_info}
         </td>
      </tr>
    </table>{/if}
  </fieldset>
</div>
