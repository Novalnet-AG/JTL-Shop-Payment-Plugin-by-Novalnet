{**
 * Novalnet admin transactions template
 * By Novalnet AG (https://www.novalnet.de)
 * Copyright (c) Novalnet AG
 *}

<script type='text/javascript' src='{$oPlugin->cAdminmenuPfadURL}js/novalnet_admin.js'></script>

<div class='adminCover'>&nbsp;</div>
    <div id='nn_header'>
        <center>
            Novalnet-Transaktions&uuml;bersicht - Bestellnummer {$nnOrderno}
        </center>
    </div>

<div class='body_div' id='overlay_window_block_body'>
    <div class='nn_accordion'>

    {if !empty($customerInfo->cVorname)}
        <div class='nn_accordion_section'>
        <a class='nn_accordion_section_title' href='#customer_data'>Kundendaten</a>
            <div id='customer_data' class='nn_accordion_section_content'>
                <table class='order_details list table'>
                    <tr class='tab_bg1'>
                        <td> Name des Kunden </td><td>{$customerInfo->cAnredeLocalized} {$customerInfo->cVorname} {$customerInfo->cNachname}</td>
                    </tr>
                    <tr class='tab_bg1'>
                        <td> Stra&szlig;e und Hausnummer </td><td>{$customerInfo->cStrasse} {$customerInfo->cHausnummer}</td>
                    </tr>
                    <tr class='tab_bg1'>
                        <td> Postleitzahl und Stadt </td><td>{$customerInfo->cPLZ} {$customerInfo->cOrt}</td>
                    </tr>
                    <tr class='tab_bg1'>
                        <td> Land </td><td>{$customerInfo->angezeigtesLand}</td>
                    </tr>
                    <tr class='tab_bg1'>
                        <td> E-Mail </td><td>{$customerInfo->cMail}</td>
                    </tr>
                    <tr class='tab_bg1'>
                        <td> Betrag </td><td>{$orderInfo->fGesamtsumme|number_format:2:",":"."}  {$currency}</td>
                    </tr>
                </table>
            </div>
        </div>
    {/if}

    {if $orderInfo->cKommentar}
        <div class='nn_accordion_section'>
            <a class='nn_accordion_section_title nn_active' href='#nn_transaction_details'>Kommentare zur Bestellung</a>
            <div id='nn_transaction_details' class='nn_accordion_section_content' style='display:block;'>
                <table class='order_details list table' id='order_comments'>
                    <tr class='tab_bg1'>
                        <td> Novalnet-Transaktionsdetails </td>
                    </tr>
                    <tr class='tab_bg1'>
                        <td>{$orderInfo->cKommentar|nl2br}</td>
                    </tr>
                </table>
            </div>
        </div>
    {/if}

    {if empty($orderInfo->nBetrag) && empty($subscriptionInfo->nSubsId)}
        <div class='nn_accordion_section'>
            <a class='nn_accordion_section_title' href='#zero_amount_booking'>Transaktion durchf&uuml;hren </a>
            <div id='zero_amount_booking' class='nn_accordion_section_content'>
                <table class='list table'>
                    <tr class='tab_bg1'>
                        <td> Buchungsbetrag der Transaktion </td>
                        <td>
                            <input type='text' id='book_amount' value='{$orderInfo->fGesamtsumme * 100}' size='7' onkeypress='return isNumberKey(event)'>(in der kleinsten W&auml;hrungseinheit, z.B. 100 Cent = entsprechen 1.00 EUR)
                        </td>
                    </tr>
                    <tr class='tab_bg1'>
                        <td></td>
                        <td colspan='3'>
                            <button type='button' class='confirm' id='amount_book' onclick='captureval({$nnOrderno},"zeroBooking")'> Buchen </button>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    {else}
        {if $orderInfo->nStatuswert|in_array:$onHoldStatus}
            <div class='nn_accordion_section'>
                <a class='nn_accordion_section_title' href='#transaction_confirmation'>Ablauf der Buchung steuern</a>
                <div id='transaction_confirmation' class='nn_accordion_section_content'>
                    <table class='list table'>
                        <tr class='tab_bg1'>
                            <td>
                                <button type='button' id='capture' class='confirm' onclick='captureval({$nnOrderno},"capture")'> Best&auml;tigen </button>
                                <button type='button' id='void' class='confirm' onclick='captureval({$nnOrderno},"void")'> Stornieren </button>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        {/if}

        {if $orderInfo->nStatuswert == 100}
            <div class='nn_accordion_section'>
                <a class='nn_accordion_section_title' href='#transaction_refund'>Ablauf der R&uuml;ckerstattung</a>
                <div id='transaction_refund' class='nn_accordion_section_content'>
                    <table class='list table'>
                    {if ($orderInfo->cZahlungsmethode|in_array:$refundOptions)}
                        <tr class='tab_bg1'>
                            <td> W&auml;hlen Sie die Option R&uuml;ckerstattung aus:</td>
                            <td>
                                <input type='radio' name='refund_amount_type' id='refund_amount_type_none' value='none' checked style='margin-right:2%'>
                                <label for='refund_amount_type_none' style='margin-right:2%'>Keiner</label>
                                <input type='radio' name='refund_amount_type' id='refund_amount_type_sepa' value='nn_sepa' style='margin-right:2%'>
                                <label for='refund_amount_type_sepa'>Novalnet Lastschrift SEPA</label>
                            </td>
                        </tr>
                        <tr class='refund_sepa tab_bg1'>
                            <td> Kontoinhaber </td>
                            <td> <input type='text' id='refund_account_holder_sepa' name= 'refund_account_holder' value='{$customerInfo->cVorname} {$customerInfo->cNachname}' onkeypress='return isAlphanumeric(event)'></td>
                        </tr>
                        <tr class='refund_sepa tab_bg1'>
                            <td> IBAN </td>
                            <td><input type='text' id='refund_account_no_sepa' name= 'refund_account_no' value='' onkeypress='return isAlphanumeric(event)'></td>
                        </tr>
                        <tr class='refund_sepa tab_bg1'>
                            <td> BIC </td>
                            <td><input type='text' id='refund_bank_code_sepa' name= 'refund_bank_code' value='' onkeypress='return isAlphanumeric(event)'></td>
                        </tr>
                    {/if}

                        <tr class='tab_bg1'>
                            <td> Geben Sie bitte den erstatteten Betrag ein </td>
                            <td> <input type='text' id='amount_refund_val' value='{$orderInfo->nBetrag}' onkeypress='return isNumberKey(event)'> (in der kleinsten W&auml;hrungseinheit, z.B. 100 Cent = entsprechen 1.00 EUR) </td>
                        </tr>

                    {if $smarty.now|date_format:'%Y-%m-%d'|strtotime > $orderInfo->dErstellt|strtotime}
                        <tr class='tab_bg1'>
                            <td> Referenz f&uuml;r die R&uuml;ckerstattung </td>
                            <td> <input type='text' id='refund_ref' value=''> </td>
                        </tr>
                    {/if}

                        <tr class='tab_bg1'>
                            <td></td>
                            <td colspan=3>
                                <button type='button' class='confirm' id='refund_update' onclick='refundval({$nnOrderno},"refund")'> Best&auml;tigen </button>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        {/if}

        {if (($orderInfo->cZahlungsmethode|in_array:$invoicePayments && $orderInfo->nStatuswert == 100 && $callbackInfo->kCallbackAmount < $orderInfo->nBetrag) || ($orderInfo->nStatuswert != 0 && $orderInfo->nStatuswert < 100 && $orderInfo->cZahlungsmethode == 'novalnet_sepa')) && $amtUpdateValid}

            <div class='nn_accordion_section'>
                <a class='nn_accordion_section_title' href='#transaction_update'>
                    {if $orderInfo->cZahlungsmethode|in_array:$invoicePayments}
                        Betrag / F&auml;lligkeitsdatum &auml;ndern
                    {else}
                        Betrag &auml;ndern
                    {/if}
                </a>

                <div id='transaction_update' class='nn_accordion_section_content'>
                    <table class='list table'>

                        {if $orderInfo->cZahlungsmethode|in_array:$invoicePayments}
                        <tr class='tab_bg1'>
                            <td> F&auml;lligkeitsdatum der Transaktion</td>
                            <td>
                                <select id='duedate_update_val_days'>

                                {if $invoiceInfo->cRechnungDuedate != '0000-00-00' && $invoiceInfo->cRechnungDuedate != ''}
                                    {assign var=duedate value="-"|explode:$invoiceInfo->cRechnungDuedate}
                                {/if}

                                {for $i=1 to 31}
                                    {if $i lt 10}
                                        {assign var="j" value="{0|cat:$i}"}
                                    {else}
                                        {assign var="j" value="$i"}
                                    {/if}

                                    <option value={$j} {if $duedate[2] == $i}
                                    selected {/if}>{$j}</option>
                                {/for}
                                </select>

                                <select id='duedate_update_val_month'>
                                {for $i=1 to 12}
                                    {if $i lt 10}
                                        {assign var="j" value="{0|cat:$i}"}
                                    {else}
                                        {assign var="j" value="$i"}
                                    {/if}

                                    <option value={$j} {if $duedate[1] == $i}
                                    selected {/if}>{$j}</option>
                                {/for}
                                </select>

                                <select id='duedate_update_val_year'>
                                {for $i={'Y'|date} to {'Y'|date+1}}
                                    <option value={$i} {if $duedate[0] == $i}
                                    selected {/if}>{$i}</option>
                                {/for}
                                </select>
                            </td>
                        </tr>
                        {/if}

                        <tr class='tab_bg1'>
                            <td> Betrag der Transaktion &auml;ndern </td>
                            <td>
                                <input type='text' id='amount_update_val' name='amount_update_val' size='5' value='{$orderInfo->nBetrag}' onkeypress='return isNumberKey(event)'> (in der kleinsten W&auml;hrungseinheit, z.B. 100 Cent = entsprechen 1.00 EUR)
                            </td>
                        </tr>

                        <tr class='tab_bg1'>
                            <td></td>
                            <td colspan=3>
                                <button name='amount_update' type='button' class='confirm' id='amount_update' onclick='amountupdate({$nnOrderno},"amountUpdate")'> &Auml;ndern </button>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        {/if}

        {if (is_object($subscriptionInfo) && $subscriptionInfo->cTerminationReason == NULL && !empty( $subscriptionInfo->nSubsId) && $orderInfo->nStatuswert <= 100)}
            <div class='nn_accordion_section'>
                <a class='nn_accordion_section_title' href='#transaction_subs_cancellation'>Abonnement k&uuml;ndigen</a>
                <div id='transaction_subs_cancellation' class='nn_accordion_section_content'>
                    <table class='list table'>
                        <tr class='tab_bg1'>
                            <td> W&auml;hlen Sie bitte den Grund aus </td>
                            <td>
                                <select id='subscribe_termination_reason'>
                                <option value='' disabled selected>--Ausw&auml;hlen--</option>
                                {foreach from=$subsReason item=value}
                                    <option value='{$value}'>{$value}</option>
                                {/foreach}
                                </select>
                            </td>
                        </tr>
                        <tr class='tab_bg1'>
                            <td></td>
                            <td><button type='button' class='confirm' id='subs_cancel' onclick='subscriptionCancel({$nnOrderno},"subsCancellation")'> Best&auml;tigen </button></td>
                        </tr>
                    </table>
                </div>
            </div>
        {/if}
    {/if}
    </div>
</div>

<div id='nn_footer'>
    <center>
        <input type='button' value='Zur&uuml;ck' class='close_button' onclick='overviewCloseButton();' id='close_button'/>
    </center>
</div>
