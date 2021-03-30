{**
 * Novalnet Invoice template
 * By Novalnet AG (https://www.novalnet.de)
 * Copyright (c) Novalnet AG
 *}

<div class="container form">
    <fieldset>
        <legend>{$smarty.session.Zahlungsart->angezeigterName[$smarty.session.cISOSprache]}</legend>

        {if $nnValidationError}
            <p class="box_error">{$nnValidationError}</p>
        {/if}

        <p class="box_info">
            {$nn_lang.invoice_description}
                {if $test_mode}
                    {$nn_lang.testmode}
                {/if}
        </p>

        <input id="nn_payment" name="nn_payment" type="hidden" value="novalnet_invoice" />
        <input id="is_fraudcheck" name="is_fraudcheck" type="hidden" value="1">
        <script type="text/javascript" src="{$paymentMethodPath}js/novalnet_invoice.js" ></script>

        {if $pin_enabled}
            <table style="width:50%">
                <tr>
                    <td valign="top"> {$nn_lang.callback_pin} </td>
                    <td valign="top">
                        <input type="text" name="nn_pin" id="nn_pin" autocomplete="off" />
                    </td>
                </tr>
                    <input type="hidden" id="nn_pin_error_message" value="{$nn_lang.callback_pin_error}">
                    <input type="hidden" id="nn_pin_empty_error_message" value="{$nn_lang.callback_pin_error_empty}">
                <tr>
                    <td></td>
                    <td valign="top">
                        <input type="checkbox" name="nn_forgot_pin" id="nn_forgot_pin" style="position:relative; left:0px; top:auto"> {$nn_lang.callback_forgot_pin}
                    </td>
                </tr>
            </table>
        {else}
            <table style="width:50%">
            {if $is_payment_guarantee}
                <tr>
                    <td valign="top">
                        {$nn_lang.guarantee_birthdate}<span style='color:red'>*</span>
                    </td>
                    <td valign="top">
                        <input type="text" value="" id="nn_dob" name="nn_dob" class="birthday form-control" placeholder="DD.MM.YYYY">
                        <input type="hidden" id="nn_dob_error_message" value="{$nn_lang.birthdate_error}">
                        <input type="hidden" id="nn_dob_valid_message" value="{$nn_lang.birthdate_valid_error}">
                        <input type="hidden" id="nn_guarantee_force"   value="{$guarantee_force}">
                    </td>
                </tr>
            {/if}

            {if $pin_by_callback}
                <tr>
                    <td valign="top">
                        {$nn_lang.callback_phone_number}<span style='color:red'>*</span>
                    </td>
                    <td valign="top">
                        <input type="text" name="nn_tel_number" id="nn_tel_number" autocomplete="off" />
                        <input type="hidden" id="nn_tele_error_message" value="{$nn_lang.callback_telephone_error}">
                    </td>
                </tr>
            {elseif $pin_by_sms}
                <tr>
                    <td valign="top">
                        {$nn_lang.callback_sms}<span style='color:red'>*</span>
                    </td>
                    <td valign="top">
                        <input type="text" name="nn_mob_number" id="nn_mob_number" autocomplete="off" />
                        <input type="hidden" id="nn_mob_error_message" value="{$nn_lang.callback_mobile_error}">
                    </td>
                </tr>
            {/if}
            </table>
        {/if}

    </fieldset>
</div>
