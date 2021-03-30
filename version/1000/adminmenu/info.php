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
 * Script : info.php
 *
 */

  global $oPlugin, $smarty;
  
  $urlpath	= gibShopURL() . '/' . PFAD_PLUGIN . $oPlugin->cVerzeichnis . '/' . PFAD_PLUGIN_VERSION . $oPlugin->nVersion . '/' . PFAD_PLUGIN_PAYMENTMETHOD . 'img/logo.png';
  $smarty->assign( 'NN_URL_PATH', gibShopURL() );
  $smarty->assign( 'url_path', $urlpath);
  print $smarty->fetch( $oPlugin->cAdminmenuPfad . 'template/info.tpl' );
?>
