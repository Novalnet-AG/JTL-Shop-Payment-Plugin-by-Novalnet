{**
 * Novalnet during order template
 * By Novalnet AG (https://www.novalnet.de)
 * Copyright (c) Novalnet AG
 *}
{if $is_nn_cc}
    {if $shopLatest}
        <div class="alert alert-info"><strong>{$browser_message}</strong></div>
        <div class="row">
            <div class="col-xs-12 col-md-6">
                {$content}
            </div>
        </div>
    {else}
        <p class="box_info"><strong>{$browser_message}</strong></p>
        <div>
            {$content}
        </div>
    {/if}
{else}
	{literal}
		<script type="text/javascript">
			$(document).ready(function(){
				$('#novalnet_checkout').submit();
			});
		</script>
	{/literal}

	{if $shopLatest}
		<div class="alert alert-info">
			<strong>
				{$browser_message}
			</strong>
		</div>
	{else}
		<p class="box_info">
			<strong>
				{$browser_message}
			</strong>
		</p>
	{/if}

	<div>
		<form method='post' action='{$paymentUrl}' id='novalnet_checkout'>
			{foreach from=$datas key='name' item='value'}
				<input type='hidden' name='{$name}' value='{$value}' />
			{/foreach}
			<input type='submit' value='{$button_text}' section='checkout' />
		</form>
	</div>
{/if}
