<?php
#########################################################
#                                                       #
#  SEPA payment method class                            #
#  This module is used for real time processing of      #
#  German Bankdata of customers.                        #
#                                                       #
#  Released under the GNU General Public License.       #
#  This free contribution made by request.              #
#  If you have found this script usefull a small        #
#  recommendation as well as a comment on merchant form #
#  would be greatly appreciated.                        #
#                                                       #
#  Script : novalnet_sepa.class.php                     #
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
class novalnet_sepa extends novalnetgateway {

  public $vendorid;
  public $productid;
  public $authcode;
  public $tariffid;
  public $testmode;
  public $manual_check_limit;
  public $productid_2;
  public $tariffid_2;
  public $novalnet_sepa_due_date;
  public $payment_name = 'novalnet_sepa';
  
  function __construct() {
    global $smarty, $oPlugin, $hinweis;
    $this->name    = NOVALNET_SEPA_WAWI_NAME;
    $this->doAssignConfigVarsToMembers();
    $this->setError();
  }

  function preparePaymentProcess($order) {
    global $Einstellungen, $DB, $smarty,  $oPlugin;
    $order_no = '';

    $order_update = $this->returnOrderType();

    if ($order_update == true || empty($_SESSION['post_array']['nn_sepaunique_id']) || empty($_SESSION['post_array']['nn_sepapanhash']) || empty($_SESSION['post_array']['sepa_owner'])) {
       $_SESSION['novalnet']['error'] = NOVALNET_UPDATE_SUCESSORDER_ERRORMSG;
       if ($order_update == true || ($_SESSION['Kunde']->nRegistriert == '0' && $_SESSION["Zahlungsart"]->nWaehrendBestellung == 0)) {
        header('Location:' . gibShopURL() . '/novalnet_return.php');
        exit;
      }
      header( 'Location:' . gibShopURL() . '/bestellvorgang.php?wk=1' );
      exit;
    }
    if ($order_update == true) {
      $Zahlung_vor_Bestell = $this->checkOrderOnUpdate($order, 'novalnetlastschriftsepa');
    }
    $this->basicValidation( $order_update );
    $amount = $this->amountConversion( $order );
    $this->doCheckManualCheckLimit( $amount );
    $params['order_no'] = ($_SESSION['Zahlungsart']->nWaehrendBestellung == 0 && $order_update == false) ? $order->cBestellNr : '';
    $params['order_comments'] = ($_SESSION['Zahlungsart']->nWaehrendBestellung == 1 && $order_update == false ) ? 'TRUE' : 'FALSE';
    $this->bulidBasicParams( $data, $order );
    $this->buildCommonParams( $data, $order, $_SESSION['Kunde'] );
    $this->additionalParams( $data, $params );
    $this->validateServerParameters($order_update, $data);
    if(!empty($order_no)){
      $data['order_no']    = $order_no;
    }

    $query    = http_build_query($data, '', '&'); 
    $url      = $this->setPaymentUrl();
    $response = $this->novalnet_debit_call( $query, $url );

    parse_str( $response, $parsed );

    unset ($_SESSION['post_array']['sepa_owner']);

   if ($_SESSION["Zahlungsart"]->nWaehrendBestellung == 0 && $order_update == false) { 
      if ($parsed['status'] == 100) {
          $this->changeOrderStatus($order);

    $comments = $this->updateOrderComments( $parsed, $order, $this->payment_name );
    $this->addReferenceToComment($order, $comments, 'FALSE'); 
    unset($_SESSION['post_array']);
    $this->sendMail($order->kBestellung, MAILTEMPLATE_BESTELLUNG_AKTUALISIERT);
    $this->postBackCall( $this->payment_name, $parsed, $order );
      }
      else {
        if ($_SESSION['Zahlungsart']->nWaehrendBestellung == 0) {
          if (($_SESSION['Kunde']->nRegistriert === 0 || $order_update == false)) {
            $this->unsetSesions();
            $_SESSION['novalnet']['error'] = utf8_decode( $parsed['status_desc'] );
            header( 'Location:' . gibShopURL() . '/novalnet_return.php' );
            exit;
          }
          $_SESSION['novalnet']['error'] = isset( $parsed['status_desc'] ) ? utf8_decode( $parsed['status_desc'] ) : '';
          header('Location:'.gibShopURL() .'/bestellvorgang.php?');
          exit;
        }
      }
    }
    else { 
      if ($parsed['status'] == 100 ) { 
        $_SESSION['novalnet']['success'] = $parsed; 
        $paymentHash = $this->generateHash( $order );
        $cReturnURL = $this->getNotificationURL( $paymentHash ) . '&sh=' . $paymentHash;
        header( 'Location:' . $cReturnURL );
        exit;
      }
      else {
        $_SESSION['novalnet']['error'] = utf8_decode( $parsed['status_desc'] );
        $cEditZahlungHinweis = utf8_decode($parsed['status_desc']);
        header( 'Location:' . gibShopURL() . '/bestellvorgang.php?editZahlungsart=1' );
        exit;
      }
    }
  }

  function handleAdditional($aPost_arr) { 
    global $smarty, $oPlugin;
	
    require_once('novalnet_css_link.php');
    $sepa_css 	= NOVALNET_SEPA_CSS_STYLE;
    $sepa_css_val = NOVALNET_SEPA_CSS_STYLE_VAL;
    $smarty->assign('sepaerror', false);
	if ( $GLOBALS['oSprache']->cISOSprache == 'ger' ){
		$lang_payment_name = ($_SESSION['Zahlungsart']->angezeigterName['ger'] != '') ? $_SESSION['Zahlungsart']->angezeigterName['ger'] : NOVALNET_SEPA_ADDITIONAL_NAME;
	}else {
		$lang_payment_name = ($_SESSION['Zahlungsart']->angezeigterName['eng'] != '') ? $_SESSION['Zahlungsart']->angezeigterName['eng'] : NOVALNET_SEPA_ADDITIONAL_NAME;
	}
	$loading_logo = gibShopURL() . '/' . PFAD_PLUGIN . 'novalnetag/version/213/paymentmethod/logos/novalnet-loading-icon.gif';
    $payment_id = $this->setPaymentKey(); 
    $description =NOVALNET_SEPA_PAYMENT_DESC;
    
    $this->novalnet_session_unset();
    $smarty->assign('lang_payment_name', $lang_payment_name);
    $smarty->assign('lang_sepa_description', $description);
    
    if ($this->testmode == '1')
      $smarty->assign('lang_sepa_test_mode_info', NOVALNET_TESTMODE_MSG);

    if (isset($aPost_arr['sepa_owner']) && !empty($aPost_arr['sepa_owner'])) {
        $aPost_arr['nn_sepaowner']           = isset($aPost_arr['sepa_owner']) ? trim($aPost_arr['sepa_owner']) : '';
        $aPost_arr['nn_sepaunique_id']       = isset($aPost_arr['sepa_unique_id']) ? trim($aPost_arr['sepa_unique_id']) : '';
        $_SESSION['novalnet']['nn_sepapanhash'] = $aPost_arr['nn_sepapanhash']  = isset($aPost_arr['sepa_pan_hash']) ? trim($aPost_arr['sepa_pan_hash']): '';
        $aPost_arr['nn_sepaibanconform']     = isset($aPost_arr['sepa_iban_conformed']) ? $aPost_arr['sepa_iban_conformed'] : '';
        $_SESSION['novalnet']['sepa_field_validator'] = $aPost_arr['sepa_field_validator']	= isset($aPost_arr['sepa_field_validator']) ? $aPost_arr['sepa_field_validator'] : '';
   }
    if(isset($_SESSION['novalnet']['email']) && $_SESSION['novalnet']['email'] != $_SESSION['Kunde']->cMail)
        $_SESSION['novalnet']['nn_sepapanhash'] ='';
    $lang_code      = $GLOBALS['oSprache']->cISOSprache == 'ger' ? 'DE' : 'EN';
    $country_code   = $_SESSION['Kunde']->cLand;
    $panhash        = ($this->autoFillFields && isset($_SESSION['novalnet']['nn_sepapanhash']))? $_SESSION['novalnet']['nn_sepapanhash'] : '';
    $fldVdr         = (($this->autoFillFields) && !empty($panhash) ? $_SESSION['novalnet']['sepa_field_validator'] : '');
    
    $customer_name 	= $_SESSION['Kunde']->cVorname.' '.$_SESSION['Kunde']->cNachname;
	$customer_name  = str_replace('&', '^', $customer_name);
    $address		= $_SESSION['Kunde']->cHausnummer . ', ' .  $_SESSION['Kunde']->cStrasse;
    $zip			= $_SESSION['Kunde']->cPLZ;
    $city			= $_SESSION['Kunde']->cOrt;
    $email			= $_SESSION['Kunde']->cMail;
	$_SESSION['novalnet']['email'] = $_SESSION['Kunde']->cMail;
    if (!$this->basicValidationOnhandleAdditional()) {
      $smarty->assign('sepaerror', true);
      return false;
    }
    $url_scheme = $this->http_url_scheme();
    $path   = "novalnet_sepa_form.php?lang_code=$lang_code&payment_id=$payment_id&country=$country_code&fldVdr=$fldVdr&panhash=$panhash";
    $path  .= "&name=$customer_name&address=$address&zip=$zip&city=$city&email=$email&url_scheme=$url_scheme";
    $smarty->assign('loading_logo_path', $loading_logo);
    $smarty->assign('path', $path);
    $smarty->assign('sepa_css', $sepa_css);
    $smarty->assign('sepa_css_val', $sepa_css_val);
    
  if (!empty($aPost_arr['nn_sepaowner']) || !empty($aPost_arr['nn_sepaunique_id']) || !empty($aPost_arr['nn_sepapanhash'] )) {
      if( $this->validateAccountDetails( $aPost_arr )) {
         $_SESSION['post_array'] = $aPost_arr;      
        return true;
      }else {
        return false;        
      }
  }
  else { 
    $smarty->assign('error', true);
    return false;
  }
  }
 
  function handleNotification($order, $paymentHash, $args) { 
    global $oPlugin;

    $amount = $this->amountConversion( $order );
    $this->doCheckManualCheckLimit( $amount ); 
    $this->changeOrderStatus($order);

    $this->postBackCall( $this->payment_name, $_SESSION['novalnet']['success'], $order );
    $paymenthash = $this->generateHash( $order );
    header ("Location:" . gibShopURL() . "/bestellabschluss.php?i=" . $paymenthash);
    exit();
  }

  /**
    * @return boolean
    * @param Bestellung $order
    * @param array $args
    */
  function finalizeOrder($order, $hash, $args){
    $response = $_SESSION['novalnet']['success'];
    return $this->verifyNotification_first($order, $hash, $args, $response);
  }
}
?>
