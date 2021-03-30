{**
 * Novalnet payment plugin
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Novalnet End User License Agreement
 *
 * DISCLAIMER
 *
 * If you wish to customize Novalnet payment extension for your needs,
 * please contact technic@novalnet.de for more information.
 *
 * @author  	Novalnet AG
 * @copyright  	Copyright (c) Novalnet
 * @license    	https://www.novalnet.de/payment-plugins/kostenlos/lizenz
 *
 * Novalnet during order template
 *}
{if !empty($is_nn_cc)}
    {if !empty($shopLatest)}
        <div class="alert alert-info"><strong>{$browserMessage}</strong></div>
        <div class="row">
            <div class="col-xs-12 col-md-6">
                {$content}
            </div>
        </div>
    {else}
        <p class="box_info"><strong>{$browserMessage}</strong></p>
        <div>
            {$content}
        </div>
    {/if}
{else}
	{literal}
		<script type="text/javascript">
			$(document).ready(function(){
		        $( '#novalnet_checkout' ).submit(function() {
		            $('input[type=submit]').attr('disabled', true);
		        });
				$('#novalnet_checkout').submit();
			});

			$(document).on("keydown", function(e){
				if (e.which && e.keyCode == 116) {
					e.preventDefault();
				}
				if (e.ctrlKey && e.shiftKey && e.keyCode == 73) {
					e.preventDefault();
				}
				if (e.ctrlKey && e.shiftKey && e.keyCode == 74) {
					e.preventDefault();
				}
				if (e.keyCode == 83 && (navigator.platform.match("Mac") ? e.metaKey : e.ctrlKey)) {
					e.preventDefault();
				}
				if (e.ctrlKey && e.keyCode == 85) {
					e.preventDefault();
				}
				if (event.keyCode == 123) {
					e.preventDefault();
				}
				if (e.which && e.ctrlKey && keycode == 82) {   
					e.preventDefault();
				}
			});
		</script>
	{/literal}

	{if !empty($shopLatest)}
		<div class="alert alert-info">
			<strong>
				{$browserMessage}
			</strong>
		</div>
	{else}
		<p class="box_info">
			<strong>
				{$browserMessage}
			</strong>
		</p>
	{/if}

	<div>
		<form method='post' action='{$paymentUrl}' id='novalnet_checkout'>
			{foreach from=$datas key='name' item='value'}
				<input type='hidden' name='{$name}' value='{$value}' />
			{/foreach}
			<input type='submit' value='{$buttonText}' section='checkout' />
		</form>
	</div>
{/if}
