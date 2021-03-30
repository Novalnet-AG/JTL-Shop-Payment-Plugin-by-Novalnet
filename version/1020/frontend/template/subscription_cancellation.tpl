{**
 * Novalnet subscription cancellation template
 * By Novalnet AG (http://www.novalnet.de)
 * Copyright (c) Novalnet AG
 *}

<div class="row">
    <div id="content" class="col-xs-6">
        <h3>{$subscription.subscription_title}</h3>
            <table class="list table">
                <tr>
                    <td>
                        {$subscription.subscription_reasons}
                    </td>
                    <td>
                    <select id="subscribe_termination_reason" class="form-control" >
                        <option value="" selected disabled>{$subscription.select_type}</option>
                            {foreach name=value from=$subsReason item=value}
                                <option value="{$value}">{$value}</option>
                            {/foreach}
                    </select>
                    </td>
                </tr>
                <tr>
                    <td></td>
                    <td>
                        <button type="button" id="subs_cancel" class="btn btn-primary" onclick="subscriptionCancel()">
                            {$subscription.confirm_btn}
                        </button>
                    </td>
                </tr>
            </table>
        <input type="hidden" id="orderno" value={$subsValue->cBestellnummer}>
    </div>
</div>
