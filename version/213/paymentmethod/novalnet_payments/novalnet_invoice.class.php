<?php
#########################################################
#                                                       #
#  Invoice payment method class                         #
#  This module is used for real time processing of      #
#  Invoice data of customers.                           #
#                                                       #
#  Released under the GNU General Public License.       #
#  This free contribution made by request.              #
#  If you have found this script usefull a small        #
#  recommendation as well as a comment on merchant form #
#  would be greatly appreciated.                        #
#                                                       #
#  Script : novalnet_invoice.class.php                  #
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
class novalnet_invoice extends novalnetgateway {
  public $vendorid;
  public $productid;
  public $authcode;
  public $tariffid;
  public $testmode;
  public $payment_duration;
  public $payment_name = 'novalnet_invoice';

  function __construct() {
    global $oPlugin,$hinweis;

    $this->name = NOVALNET_INVOICE_WAWI_NAME;
    $this->doAssignConfigVarsToMembers( $this->payment_name );
    $this->setError();
  }


  function preparePaymentProcess($order){
    global $Einstellungen, $DB, $smarty, $oPlugin;
    $order_no = '';
    $due_date = '';
    $order_update = $this->returnOrderType();
    $this->novalnet_session_unset();
    if ($order_update == true) {
      $Zahlung_vor_Bestell = $this->checkOrderOnUpdate($order, 'novalnetrechnung');
    }
    $this->basicValidation( $order_update );
    if (!empty($this->payment_duration) && !is_numeric($this->payment_duration)){
	$_SESSION['novalnet']['error'] = NOVALNET_INVOICE_DUE_DATE_ERROR_MSG;
    }
    $this->setOrderNumber($order_update, $Zahlung_vor_Bestell, $order, $order_no);
    $params['order_comments'] = ($_SESSION['Zahlungsart']->nWaehrendBestellung == 1 && $order_update == false ) ? 'TRUE' : 'FALSE';
    $data = array();
    $this->bulidBasicParams( $data, $order );
    $this->buildCommonParams( $data, $order, $_SESSION['Kunde'] );
    $this->additionalParams( $data, $params );
    $this->validateServerParameters($order_update, $data);
    if($this->payment_duration !='' && $this->isDigits($this->payment_duration)) {
      $data['due_date'] =  date("Y-m-d",mktime(0,0,0,date("m"),(date("d")+$this->payment_duration),date("Y")));
    }

    $urlparam = http_build_query( $data, '', '&' );
    $url      = $this->setPaymentUrl();
    $response = $this->novalnet_debit_call( $urlparam, $url );
    parse_str( $response, $parsed );

    if (isset($parsed['status']) && $parsed['status'] != 100 && $order_update == true) {
      $_SESSION['novelnet']['error'] = isset( $parsed['status_desc'] ) ? utf8_decode( $parsed['status_desc'] ) : '';
      header( 'Location:' . gibShopURL() . '/novalnet_return.php?status_desc='. $_SESSION['novelnet']['error']);
      exit;
    }
	$pos = preg_match( '/bestellab_again.php/' , basename($_SERVER['REQUEST_URI']));
	if ($pos){ 
		if ($parsed['status'] == 100) {
        $_SESSION['novalnet_status'] = NOVALNET_ORDER_SUCESS_MSG;
        $comments = $this->updateOrderComments( $parsed, $order, $this->payment_name );
        $this->addReferenceToComment( $order, $comments, 'FALSE' );
        $this->postBackCall( $this->payment_name, $parsed, $order );
      }
      else {
          if($_SESSION['Zahlungsart']->nWaehrendBestellung == 0) {
            if (($_SESSION['Kunde']->nRegistriert === 0 || $order_update == false)) {
              $this->unsetSesions();
              if (isset( $_SESSION['kommentar'] ))
                  unset( $_SESSION['kommentar'] );
              $_SESSION['novalnet']['error'] = utf8_decode( $parsed['status_desc'] );
              header( 'Location:' . gibShopURL() . '/novalnet_return.php' );
              exit;
            }
            $_SESSION['novalnet']['error'] = isset( $parsed['status_desc'] ) ? utf8_decode( $parsed['status_desc'] ) : '';
            if ($order_update == true) {
              header( 'Location:' . gibShopURL() . '/bestellvorgang.php?wk=1' );
              exit;
            }
          }
	    }
	}
    elseif (isset( $_SESSION['novalnet']['nWaehrendBestellung'] ) &&  $_SESSION['novalnet']['nWaehrendBestellung']  == 'Nein') {
      if ($parsed['status'] == 100) {
        $_SESSION['novalnet_status'] = NOVALNET_ORDER_SUCESS_MSG;
        $comments = $this->updateOrderComments( $parsed, $order, $this->payment_name );
        $this->addReferenceToComment( $order, $comments, 'FALSE' );
        if (isset($_SESSION['novalnet']['reorder']) && $_SESSION['novalnet']['reorder'] == 'Ja') {
          $this->postBackCall( $this->payment_name, $parsed, $order );
          $paymentHash = $this->generateHash( $order );
          header( 'Location:' . gibShopURL() . '/bestellabschluss.php?i=' . $paymentHash );
          exit;
        }
        $this->postBackCall( $this->payment_name, $parsed, $order );
        $this->sendMail($order->kBestellung, MAILTEMPLATE_BESTELLUNG_AKTUALISIERT);
      }
      else {
          if($_SESSION['Zahlungsart']->nWaehrendBestellung == 0) {
            if (($_SESSION['Kunde']->nRegistriert === 0 || $order_update == false)) {
              $this->unsetSesions();
              if (isset( $_SESSION['kommentar'] ))
                  unset( $_SESSION['kommentar'] );
              $_SESSION['novalnet']['error'] = utf8_decode( $parsed['status_desc'] );
              header( 'Location:' . gibShopURL() . '/novalnet_return.php' );
              exit;
            }
            $_SESSION['novalnet']['error'] = isset( $parsed['status_desc'] ) ? utf8_decode( $parsed['status_desc'] ) : '';
            if ($order_update == true) {
              header( 'Location:' . gibShopURL() . '/bestellvorgang.php?wk=1' );
              exit;
            }
          }
        header( 'Location:' . gibShopURL() . '/bestellvorgang.php?editZahlungsart=1&' . SID );
        exit;
      }
    }
    else { 
      if($parsed['status'] == 100) {
        $_SESSION['novalnet']['success'] = $parsed;
        $paymentHash  = $this->generateHash( $order );
        $cReturnURL   = $this->getNotificationURL( $paymentHash ) . '&sh=' . $paymentHash;
        header( 'Location:' . $cReturnURL );
        exit;
      }
      else {
        $_SESSION['novalnet']['error'] = utf8_decode( $parsed['status_desc'] );
        if ($order_update == true) {
          header( 'Location:' . gibShopURL() . '/bestellvorgang.php?wk=1' );
          exit;
        }
        header( 'Location:' . gibShopURL() . '/bestellvorgang.php?editZahlungsart=1&' . SID );
        exit;
      }
    }
  }


  function handleNotification($order, $paymentHash, $args) {
    global $oPlugin;
    $this->postBackCall( $this->payment_name, $_SESSION['novalnet']['success'], $order );
    $paymenthash = $this->generateHash( $order );
    unset( $_SESSION['novalnet'] );
    header( 'Location:' . gibShopURL() . '/bestellabschluss.php?i=' . $paymenthash );
    exit();
  }

  function finalizeOrder($order, $hash, $args) {
    $response = $_SESSION['novalnet']['success'];
	
  return $this->verifyNotification_first( $order, $hash, $args, $response );
  }

}
