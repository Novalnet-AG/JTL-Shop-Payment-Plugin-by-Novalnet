<script type="text/javascript" src="{$paymentMethodURL}js/novalnet.js" ></script>
<div class="row">
	<div class="col-xs-12 col-md-6">
		<div id="nn_loader" style="display:block">
				<script type="text/javascript">
				{literal}
					jQuery(document).ready(function(){
						$('#novalnet_checkout').submit();
					});
					function hideLoadingImage() {
						$('#nn_loader').css('display','none');
					}
				{/literal}	
				</script>
		</div>
		<form method='post' action='{$paymentUrl}' id='novalnet_checkout' target="novalnet_cc_iframe">
			{foreach from=$datas key='name' item='value'}
				<input type='hidden' name='{$name}' value='{$value}' />
			{/foreach}
			<iframe name="novalnet_cc_iframe" width="750" height="500" frameBorder="0" scrolling="no" onload="hideLoadingImage()"></iframe>
		</form>
	</div>
</div>		
