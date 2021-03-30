<?php
#########################################################
#                                                       #
#  Telephone payment method class                       #
#  This module is used for real time processing of      #
#  Austrian Bankdata of customers.                      #
#                                                       #
#  Released under the GNU General Public License.       #
#  This free contribution made by request.              #
#  If you have found this script usefull a small        #
#  recommendation as well as a comment on merchant form #
#  would be greatly appreciated.                        #
#                                                       #
#  Script : novalnet_telefon.class.php                  #
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
class novalnet_telefon extends novalnetgateway {

  public $vendorid;
  public $productid;
  public $authcode;
  public $tariffid;
  public $testmode;
  public $payment_name = 'novalnet_telefon';

  /**
  * Constructor
  */
  function init() {
    global $oPlugin, $hinweis;

    $this->name    = NOVALNET_TELE_WAWI_NAME;
    $this->doAssignConfigVarsToMembers();
    $this->setError();
  }

  function preparePaymentProcess($order) {
    global $Einstellungen, $DB, $smarty, $oPlugin, $cEditZahlungHinweis;
	if ($_SESSION["Zahlungsart"]->nWaehrendBestellung != "1") {
		if ( $GLOBALS['oSprache']->cISOSprache == 'ger') {
				$_SESSION['novalnet']['error'] = NOVALNET_UPDATE_SUCESSORDER_ERRORMSG;
				header('Location:'.URL_SHOP .'/novalnet_return.php');
				exit;
		}
	}
    $order_update = $this->returnOrderType();
    if ($order_update == true) {
        $_SESSION['novalnet']['error'] = NOVALNET_UPDATE_SUCESSORDER_ERRORMSG;
        header('Location:'.URL_SHOP .'/novalnet_return.php');
        exit;
    }
    if (empty($_SESSION['novalnet']['tid'])) {
      $this->basicValidation( $order_update );
    }
    $amount = $this->amountConversion( $order );
    if (!empty($_SESSION['novalnet']['tid']) && $amount != $_SESSION['novalnet']['tot_amt']) {
      $_SESSION['tele']['error'] = NOVALNET_TELE_AMOUNT_CHANGED_ERROR;
      header('Location:'.gibShopURL() .'/bestellvorgang.php?editZahlungsart=1&');
      exit;
    }
    if ($amount < 99 || $amount > 1000) {
      $_SESSION['novalnet']['error'] = NOVALNET_TELE_AMOUNT_RANGE_ERROR;
      header('Location:'.gibShopURL() .'/bestellvorgang.php?editZahlungsart=1&');
      exit;
    }
    if (empty($_SESSION['novalnet']['tid'])) {
      $params['order_no'] = ($_SESSION["Zahlungsart"]->nWaehrendBestellung == 0 && $order_update == false) ? $order->cBestellNr : '';
      $params['order_comments'] = ($_SESSION['Zahlungsart']->nWaehrendBestellung == 1 && $order_update == false ) ? 'TRUE' : 'FALSE';
      $this->bulidBasicParams( $data, $order );
      $this->buildCommonParams( $data, $order, $_SESSION['Kunde'] );
      $this->additionalParams( $data, $params );
      $this->validateServerParameters( $order_update, $data );
      if (!empty($order_no)){
        $data['order_no']    = $order_no;
      }
      $query    = http_build_query($data, '', '&');
      $url      = $this->setPaymentUrl();
      $response = $this->novalnet_debit_call( $query, $url );
      parse_str( $response, $parsed );
      if ($parsed['status'] == 100) {
        if (isset($parsed['amount']) && !empty($parsed['amount']) && strpos($parsed['amount'], ".") !== false) {
           $amount = str_replace('.', '', $parsed['amount']);
        }
        $_SESSION['novalnet']['tid']        = $parsed['tid'];
        $_SESSION['novalnet']['test_mode']  = $parsed['test_mode'];
        $_SESSION['novalnet']['tel_number'] = $parsed['novaltel_number'];
        $_SESSION['novalnet']['tot_amt']    = $amount;
        $_SESSION['novalnet']['tot_amt_fmt']= number_format($parsed['amount'], 2, ',', '.')." ".$order->Waehrung->cISO;
        $_SESSION['novalnet']['user_cmts']  = $parsed['inputval1'];
        $_SESSION['novalnet']['status']     = $parsed['status'];
        $_SESSION['novalnet']['success']    = $parsed;
        $tel_no = $this->getFormatedTelNumber ($_SESSION['novalnet']['tel_number']);
        $_SESSION['novalnet']['error'] =  NOVALNET_TELE_PAYMENT_STEPS . '<br><br><b>' . NOVALNET_TELE_PAYMENT_STEPONE . '</b>' .
                                        NOVALNET_TELE_PAYMENT_STEPONE_DESC_ONE . '<b>' . $tel_no . '</b><br>' .
                                        NOVALNET_TELE_PAYMENT_STEPONE_DESC_TWO . '<b>' . $_SESSION['novalnet']['tot_amt_fmt'] .'</b>'.  NOVALNET_TELE_PAYMENT_STEPONE_DESC_THREE . '<br><br><b>'.NOVALNET_TELE_PAYMENT_STEPTWO . '</b>'. NOVALNET_TELE_PAYMENT_STEPTWO_DESC;

        header('Location:'.gibShopURL() .'/bestellvorgang.php?editZahlungsart=1&');
        exit;
      }
      else {
        $_SESSION['novalnet']['error'] = utf8_decode($parsed['status_desc']);
        header('Location:'.gibShopURL() .'/bestellvorgang.php?editZahlungsart=1&');
        exit;
      }
    }
    $url      = 'https://payport.novalnet.de/nn_infoport.xml';
    $language  = $GLOBALS['oSprache']->cISOSprache == 'ger' ? 'DE' : 'EN';
    if (empty($_SESSION['novalnet']['tid']) || !$this->isDigits($this->vendorid) || empty($this->authcode)) {
      $_SESSION['tele']['error'] = NOVALNET_SECONDCALL_BASIC_ERROR;
      unset($_SESSION['novalnet']['tid']);
      header('Location:'.gibShopURL() .'/bestellvorgang.php?editZahlungsart=1&');
      exit;
    }
    $urlparam  = '<nnxml><info_request><vendor_id>' . $this->vendorid . '</vendor_id>';
    $urlparam .= '<vendor_authcode>' . $this->authcode . '</vendor_authcode>';
    $urlparam .= '<request_type>NOVALTEL_STATUS</request_type><tid>' . $_SESSION['novalnet']['tid'] . '</tid>';
    $urlparam .= '<lang>' . $language . '</lang></info_request></nnxml>';
    $data = $this->novalnet_debit_call($urlparam, $url);
    if (strstr($data, '<novaltel_status>')) {
      preg_match('/novaltel_status>?([^<]+)/i', $data, $matches);
      $aryResponse['status'] = $matches[1];

      preg_match('/novaltel_status_message>?([^<]+)/i', $data, $matches);
      $aryResponse['status_desc'] = $matches[1];
    }
    else {
       parse_str( $data, $aryPaygateResponse );
    }
//Start : For Manual Testing
$aryResponse['status'] = 100;
//End   : For Manual Testing

    if ($aryResponse['status'] == 100 ) {
        $paymentHash = $this->generateHash($order);
        $cReturnURL = $this->getNotificationURL($paymentHash) . '&sh=' . $paymentHash;
        header('Location:'.$cReturnURL);
        exit;
      }
      else {
        $_SESSION['novalnet']['error'] = utf8_decode($aryResponse['status_desc']);
        $cEditZahlungHinweis = utf8_decode($aryResponse['status_desc']);
        header('Location:'.gibShopURL() .'/bestellvorgang.php?editZahlungsart=1');
        exit;
      }
  }

  function handleNotification($order, $paymentHash, $args) {
    global $oPlugin;

    $this->changeOrderStatus($order);
    $this->postBackCall( $this->payment_name, $_SESSION['novalnet']['success'], $order );
    $paymenthash = $this->generateHash( $order );
    header ("Location: " . gibShopURL() . "/bestellabschluss.php?i=" . $paymenthash);
    exit();

  }

  function finalizeOrder($order, $hash, $args) {
    $response = $_SESSION['novalnet']['success'];
    return $this->verifyNotification_first($order, $hash, $args, $response);
  }
}
