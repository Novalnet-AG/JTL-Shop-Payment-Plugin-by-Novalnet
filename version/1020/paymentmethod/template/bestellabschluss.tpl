{if $is_iframe}
<div class="alert alert-info"><strong>{$browser_text}</strong></div>
    <div class="row">
        <div class="col-xs-12 col-md-6">
            {$content}
        </div>
    </div>
{else if $is_redirect}
    {literal}
        <script type="text/javascript">
            $(document).ready(function(){
                $('#novalnet_checkout').submit();
            });
        </script>
    {/literal}
    <div class="alert alert-info"><strong>{$browser_text}</strong></div>
    <div>
        <form method='post' action='{$paymentUrl}' id='novalnet_checkout'>
            {foreach from=$datas key='name' item='value'}
                <input type='hidden' name='{$name}' value='{$value}' />
            {/foreach}
            <input type='submit' value='{$button_text}' section='checkout' />
        </form>
    </div>
{/if}

