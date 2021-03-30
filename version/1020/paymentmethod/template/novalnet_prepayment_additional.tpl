<fieldset>
    {if $error}
        <div class="alert alert-danger">{$error_desc}</div>
    {/if}
</fieldset>
<fieldset>
    <legend>{$payment_name}</legend>
        <div class="alert alert-info">
            {$nn_lang.invoice_description}
                {if $test_mode}
                    {$nn_lang.testmode}
                {/if}
        </div>
    <input id="nn_payment" name="nn_payment" type="hidden" value="{$payment_name}" />
</fieldset>
