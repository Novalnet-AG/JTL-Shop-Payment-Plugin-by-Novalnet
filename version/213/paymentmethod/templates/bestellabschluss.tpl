<div style="margin:10px 0;">
  {if $status}
    {$status}
  {else}
    {php} echo $_SESSION['novalnet_status'];{/php}
  {/if}
</div>
