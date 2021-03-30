{literal}
  <script type="text/javascript">
    var showbaby;
    function show_acdc_info() {
      var ishttps=document.location.protocol;
      var acdclink= ishttps.concat('//www.novalnet.de/img/acdc_info.png');
      var url=parent.location.href;url= acdclink;w='550';h='300';x=screen.availWidth/2-w/2;y=screen.availHeight/2-h/2;showbaby=window.open(url,'showbaby','toolbar=0,location=0,directories=0,status=0,menubar=0,resizable=1,width='+w+',height='+h+',left='+x+',top='+y+',screenX='+x+',screenY='+y);showbaby.focus();
    }
    function hide_acdc_info() {
      showbaby.close();
    }
  </script>
{/literal}
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

    <table style="width:100%;">
      <tr>
        <td width="15%" valign="top"> {$lang_account_holder} </td>
        <td valign="top"><input type="text" name="nn_holdername" value="{$de_acc_name}"  autocomplete="off" maxlength="40" > </td>
      </tr>
      <tr>
         <td valign="top"> {$lang_account_number} </td>
         <td valign="top"><input type="text" name="nn_accountnumber" value="{$de_acc_no}"  autocomplete="off" maxlength="40"> </td>
      </tr>
      <tr>
         <td valign="top"> {$lang_bank_code} </td>
         <td valign="top"><input type="text" name="nn_bankcode" value="{$de_bank_code}"  autocomplete="off" maxlength="40"> </td>
      </tr>
      {if $acdc == 1}
        <tr>
          <td valign="top"></td>
          <td valign="top">
            <input type="checkbox" name="nn_acdc" value=1 > <a onmouseover="show_acdc_info()" href="javascript:show_acdc_info()">{$lang_acdc}*</a>
          </td>
        </tr>
      {/if}
      <tr>
         <td valign="top"> &nbsp; </td>
         <td valign="top">{$lang_de_description}
          {$lang_elv_de_test_mode_info}
         </td>
      </tr>
    </table>
  </fieldset>
</div>
