<?php
/**
 * Novalnet payment method module
 * This module is used for real time processing of
 * Novalnet transaction of customers.
 *
 * Copyright (c) Novalnet AG
 *
 * Released under the GNU General Public License
 * This free contribution made by request.
 * If you have found this script useful a small
 * recommendation as well as a comment on merchant form
 * would be greatly appreciated.
 *
 * Script : novalnet_admin.php
 *
 */

  global $oPlugin, $smarty;

  $smarty->assign( 'NN_URL_PATH', gibShopURL() );
  print $smarty->fetch( $oPlugin->cAdminmenuPfad . 'template/novalnet_admin.tpl' );
?>
