<?php
global $oPlugin;
global $smarty;

  $url_path	= gibShopURL().'/includes/plugins/novalnetag/version/213/paymentmethod/logos/logo.png';
  $smarty->assign( 'URL_SHOP', URL_SHOP );
  $smarty->assign( 'ishttps', $ishttps );
  $smarty->assign( 'url_path', $url_path);
  print $smarty->fetch( $oPlugin->cAdminmenuPfad . 'tpl/info.tpl' );
?>
