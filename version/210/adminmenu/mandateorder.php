<?php
global $oPlugin;
global $smarty;

require_once( 'includes/admininclude.php' );
require_once( PFAD_ROOT . PFAD_ADMIN . PFAD_INCLUDES . 'blaetternavi.php' );
require_once( PFAD_ROOT . PFAD_CLASSES . 'class.JTL-Shop.Bestellung.php' );
require_once( PFAD_ROOT . PFAD_ADMIN . PFAD_INCLUDES . 'bestellungen_inc.php' );
$cHinweis = '';
$cFehler = '';
$step = 'bestellungen_uebersicht';
$cSuchFilter = '';
$nAnzahlProSeite = '';
$oBlaetterNaviConf = baueBlaetterNaviGetterSetter( 1, $nAnzahlProSeite );

if (verifyGPCDataInteger( 'zuruecksetzen' ) == 1) {
  switch (setzeAbgeholtZurueck( $_POST['kBestellung'] )) {
  case 0 - 1: {
      $cHinweis = 'Ihr markierten Bestellungen wurden erfolgreich zur&uuml;ckgesetzt.';
      break;
    }

  case 1: {
      $cFehler = 'Fehler: Bitte markieren Sie mindestens eine Bestellung.';
    }
  }
}
else {
  if (verifyGPCDataInteger( 'Suche' ) == 1) {
    $cSuche = filterXSS( verifyGPDataString( 'cSuche' ) );

    if (0 < strlen( $cSuche )) {
      $cSuchFilter = $cSuche;
    }
    else {
      $cFehler = 'Fehler: Bitte geben Sie eine Bestellnummer ein.';
    }
  }
}


if ($step == 'bestellungen_uebersicht') {
  $Zahlung_vor_Bestell = $GLOBALS['DB']->executeQuery("select kZahlungsart,nWaehrendBestellung, nMailSenden from tzahlungsart where cModulId LIKE '%novalnetlastschriftsepa%'", 1);
  $smarty->assign('novalnetsepa', $Zahlung_vor_Bestell->kZahlungsart);
  $smarty->assign('oBestellung_arr', gibBestellungsUebersicht( $oBlaetterNaviConf->cSQL1, $cSuchFilter ) );
}

  $smarty->assign( 'cHinweis', $cHinweis );
  $smarty->assign( 'cFehler', $cFehler );
  if (0 < strlen( $cSuchFilter )) {
    $smarty->assign( 'cSuche', $cSuchFilter );

  }

  print $smarty->fetch( $oPlugin->cAdminmenuPfad . 'tpl/mandateorder.tpl' );

?>
