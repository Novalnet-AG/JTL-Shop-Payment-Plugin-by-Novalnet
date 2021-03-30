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
         <td valign="top"><input type="text" name="nn_holdername" autocomplete="off" maxlength="40" value="{$at_acc_name}" /> </td>
          </tr>
      <tr>
         <td valign="top"> {$lang_account_number} </td>
         <td valign="top"><input type="text" name="nn_accountnumber" autocomplete="off" maxlength="40" value="{$at_acc_no}" /> </td>
      </tr>
      <tr>
         <td valign="top"> {$lang_bank_code} </td>
         <td valign="top"><input type="text" name="nn_bankcode" autocomplete="off" maxlength="40" value="{$at_bank_code}" /> <br><br>{$lang_at_description}
        {$lang_elv_at_test_mode_info}
         </td>
      </tr>
    </table>
  </fieldset>
</div>
