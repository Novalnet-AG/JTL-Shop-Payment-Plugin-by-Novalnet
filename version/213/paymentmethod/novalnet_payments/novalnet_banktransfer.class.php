<?php

#########################################################
#                                                       #
#  Sofortüberweisung / INSTANTBANKTRANSFER payment      #
#  method class                                         #
#  This module is used for real time processing of      #
#  German Bankdata of customers.                        #
#                                                       #
#  Released under the GNU General Public License.       #
#  This free contribution made by request.              #
#  If you have found this script usefull a small        #
#  recommendation as well as a comment on merchant form #
#  would be greatly appreciated.                        #
#                                                       #
#  Script : novalnet_banktransfer.class.php             #
#                                                       #
#########################################################
include_once( PFAD_ROOT . PFAD_INCLUDES_MODULES.'PaymentMethod.class.php' );
require_once( PFAD_ROOT . PFAD_CLASSES . 'class.JTL-Shop.Jtllog.php' );
require_once( 'class.JTL-Shop.Novalnet.php' );
  if ($GLOBALS['oSprache']->cISOSprache == 'ger') {
    require_once( dirname(dirname(__FILE__)) . '/lang/de_DE.php' );
  }
  else {
    require_once( dirname(dirname(__FILE__)) . '/lang/en_GB.php' );
  }
ob_start();
class novalnet_banktransfer extends novalnetgateway {

  public $vendorid;
  public $productid;
  public $authcode;
  public $tariffid;
  public $testmode;
  public $key_password;
  public $payment_name = 'novalnet_bank';

  function __construct() {
    global $DB, $cEditZahlungHinweis, $smarty, $hinweis, $oPlugin;
	
    if ( $GLOBALS['oSprache']->cISOSprache == 'ger' ){
        $this->name = "Novalnet Sofortüberweisung";
    } else {
        $this->name = "Novalnet Instant Bank Transfer";
    }
    $this->doAssignConfigVarsToMembers();
    $this->setError();
    if (isset($_SESSION['novalnet']['nWaehrendBestellung']) && $_SESSION['novalnet']['nWaehrendBestellung'] == 'Nein') {  
      if (isset($_REQUEST['status']) && $_REQUEST['status'] && $_REQUEST['paymentname'] == 'novalnet_bank') {
        $this->orderComplete($_REQUEST);
      }
    }
    
    if (isset($_REQUEST['status']) && $_REQUEST['status'] != 100 && !empty( $_REQUEST['status_text'] ) && $_REQUEST['paymentname'] == 'novalnet_bank') { 
      $hinweis = utf8_decode( $_REQUEST['status_text'] );
    }
  }

  function preparePaymentProcess($order) {
    global $Einstellungen, $DB, $smarty, $oPlugin;
    $order_no = '';
    $params = array();
	$Zahlung_vor_Bestell = '';
    $this->novalnet_session_unset();
    $order_update = $this->returnOrderType();
    if ($order_update == true) {
      $Zahlung_vor_Bestell = $this->checkOrderOnUpdate($order, 'novalnetsofortüberweisung');
    }
    $this->basicValidation( $order_update );
    $params['uniqueid']     = uniqid();
    $params['cFailureURL']  = gibShopURL() . '/bestellvorgang.php?editZahlungsart=1&' . SID;
    $this->setReturnUrls( $params, $order_update, $Zahlung_vor_Bestell, $order);
    $_SESSION['novalnet']['order']        = $order;
    $params['order_comments'] = ($_SESSION['Zahlungsart']->nWaehrendBestellung == 1 && $order_update == false ) ? 'TRUE' : 'FALSE';

    $this->bulidBasicParams( $data, $order, $params['uniqueid'] );
    $this->buildCommonParams( $data, $order, $_SESSION['Kunde'] );
    $this->additionalParams( $data, $params );
    $this->validateServerParameters($order_update, $data);
    if (!empty( $params['order_no'] )) {
      $data['order_no']    = $params['order_no'];
    }

    $form_action_url = $this->setPaymentUrl();
    if (!empty($form_action_url) && !empty($data)) {
      $buildForm = $this->buildRedirectionForm($data);
//Guest user command line      
      if ($_SESSION['Kunde']->nRegistriert === 0 && $_SESSION["Zahlungsart"]->nWaehrendBestellung == 0) {
        unset($_SESSION['Kunde']);
        unset($_SESSION['Warenkorb']);
      }
      if ($_SESSION["Zahlungsart"]->nWaehrendBestellung == 0 && $order_update == false) {
        unset($_SESSION['Warenkorb']);
        if ($_SESSION['Kunde']->nRegistriert != 0) {
          $GLOBALS["DB"]->executeQuery("delete wpp from twarenkorbperspos as wpp left join twarenkorbpers as wp on wpp.kWarenkorbPers= wp.kWarenkorbPers  where wp.kKunde='".$customer->kKunde."'", 4);
        }
        if (!empty($_SESSION['Kupon'])) {
          unset($_SESSION['Kupon']);
        }
      }
// end      
      echo $buildForm;
      exit();
      }
    }

  function handleNotification($order, $paymentHash, $args) {
    global $oPlugin;

    $this->changeOrderStatus($order);
    $this->postBackCall($this->payment_name, $_REQUEST, $order);
    $paymenthash = $this->generateHash( $order );
    unset($_SESSION['novalnet']);
    header ("Location:" . gibShopURL() . "/bestellabschluss.php?i=" . $paymenthash);
  }

  function finalizeOrder($order, $hash, $args) { 
     return $this->verifyNotification_first( $order, $hash, $args, $_REQUEST );
  }

}
