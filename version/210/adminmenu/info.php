<?php
global $oPlugin;
global $smarty;

   
  $ishttps = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) ? 'https://' : 'http://';
  $url_path	= URL_SHOP.'/includes/plugins/novalnetag/version/211/paymentmethod/logos/NN_Logo_T.png';
  $smarty->assign( 'URL_SHOP', URL_SHOP );
  $smarty->assign( 'ishttps', $ishttps );
  $smarty->assign( 'url_path', $url_path);
  print $smarty->fetch( $oPlugin->cAdminmenuPfad . 'tpl/info.tpl' );
?>
