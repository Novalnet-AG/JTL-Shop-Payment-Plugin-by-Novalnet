{**
 * Novalnet subscription cancellation template
 * By Novalnet (https://www.novalnet.de)
 * Copyright (c) Novalnet
 *}
{if !$isMobileTemplate}
	<div class="container form">
		<legend>{$subscription.subscription_title}</legend>
			<table style="width:50%">
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
{else}
	<li class="ui-li ui-li-divider ui-bar-d" data-role="list-divider" role="heading">
		{$subscription.subscription_title}
	</li>
	<li class="ui-li ui-li-static ui-btn-up-c ui-last-child">
		<p class="ui-li-desc">{$subscription.subscription_reasons}
			<select id="subscribe_termination_reason" class="form-control" >
				<option value="" selected disabled>{$subscription.select_type}</option>
					{foreach name=value from=$subsReason item=value}
						<option value="{$value}">{$value}</option>
					{/foreach}
			</select>
		</p>
		<p>
			<button type="button" id="subs_cancel" class="btn btn-primary" onclick="subscriptionCancel()">
				{$subscription.confirm_btn}
			</button>
		</p>
	</li>
{/if}

<div id='nn_loader' style='display:none'>
    {literal}
		<style type='text/css'>
            #nn_loader {
                position  : fixed;
                left      : 0px;
                top       : 0px;
                width     : 100%;
                height    : 100%;
                z-index   : 9999;
                background: url('{/literal}{$loadingimgUrl}{literal}') 50% 50% no-repeat;
            }
		</style>
	{/literal}
</div>
