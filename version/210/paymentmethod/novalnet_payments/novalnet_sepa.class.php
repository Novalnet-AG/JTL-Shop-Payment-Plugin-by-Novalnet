<?php
#########################################################
#                                                       #
#  SEPA payment method class 			                #
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
class novalnet_sepa extends novalnetgateway {

  public $vendorid;
  public $productid;
  public $authcode;
  public $tariffid;
  public $testmode;
  public $manual_check_limit;
  public $productid_2;
  public $tariffid_2;
  public $sepapayment_type;
  public $novalnet_sepa_due_date;
  public $payment_name = 'novalnet_sepa';
  public $additional_key = 55;

  function init() {
    global $smarty, $oPlugin, $hinweis;
    $this->name    = NOVALNET_SEPA_WAWI_NAME;
    $this->doAssignConfigVarsToMembers();	
    $this->setError();
  }

  function preparePaymentProcess($order){
    global $Einstellungen, $DB, $smarty,  $oPlugin;	
    $order_no = '';

    $order_update = $this->returnOrderType();

    if ($order_update == true || empty($_SESSION['post_array']['nn_sepaunique_id']) || empty($_SESSION['post_array']['nn_sepapanhash']) || empty($_SESSION['post_array']['sepa_owner'])) {
       $_SESSION['novalnet']['error'] = NOVALNET_UPDATE_SUCESSORDER_ERRORMSG;
       if ($order_update == true || ($_SESSION['Kunde']->nRegistriert == '0' && $_SESSION["Zahlungsart"]->nWaehrendBestellung == 0)) {
        header('Location:' . URL_SHOP . '/novalnet_return.php');
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
    $params['order_no'] = ($_SESSION["Zahlungsart"]->nWaehrendBestellung == 0 && $order_update == false) ? $order->cBestellNr : '';
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

		if($this->sepapayment_type == 'DIRECT_DEBIT_SEPA') {
			$this->changeOrderStatus($order);
		}
		if($this->sepapayment_type == 'DIRECT_DEBIT_SEPA_SIGNED' && isset($data['mandate_present']) && $data['mandate_present'] == 1) {
			$this->changeOrderStatus($order);
		}
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
            header( 'Location:' . URL_SHOP . '/novalnet_return.php' );
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
    $smarty->assign('sepaerror', false);
	if ( $GLOBALS['oSprache']->cISOSprache == 'ger' ){
		$lang_payment_name = ($_SESSION['Zahlungsart']->angezeigterName['ger'] != '') ? $_SESSION['Zahlungsart']->angezeigterName['ger'] : NOVALNET_SEPA_ADDITIONAL_NAME;
	}else {
		$lang_payment_name = ($_SESSION['Zahlungsart']->angezeigterName['eng'] != '') ? $_SESSION['Zahlungsart']->angezeigterName['eng'] : NOVALNET_SEPA_ADDITIONAL_NAME;
	}
	$novalnet_protocol = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) ? 'https://' : 'http://';
    if($this->sepapayment_type == 'DIRECT_DEBIT_SEPA') {
		  $payment_id = $this->setPaymentKey(); 
      $description =NOVALNET_SEPA_PAYMENT_DESC;
    } else{
   		$payment_id = $this->setPaymentKey();
      $description =NOVALNET_SEPASIGNED_PAYMENT_DESC;
    }
    unset($_SESSION['novalnet']['nnde_holdername'],$_SESSION['novalnet']['nnde_accountnumber'], $_SESSION['novalnet']['nnde_bankcode'], $_SESSION['novalnet']['nnde_acdc'], $_SESSION['novalnet']['nnat_holdername'],$_SESSION['novalnet']['nnat_accountnumber'], $_SESSION['novalnet']['nnat_bankcode'], $_SESSION['novalnet']['cc_pan_hash'], $_SESSION['novalnet']['cc_unique_id'], $_SESSION['novalnet']['cc_fldvalidator'] );

    $smarty->assign('novalnet_protocol', $novalnet_protocol);
    $smarty->assign('lang_payment_name', $lang_payment_name);

    $smarty->assign('lang_field_validation_message', NOVALNET_ACCOUNT_INFO_MSG);
    $smarty->assign('lang_sepa_description', $description);
	
    if ($this->testmode == '1')
      $smarty->assign('lang_sepa_test_mode_info', NOVALNET_TESTMODE_MSG);

    if (isset($aPost_arr['sepa_owner']) && !empty($aPost_arr['sepa_owner'])) {
		$aPost_arr['nn_sepaowner']           = isset($aPost_arr['sepa_owner']) ? trim($aPost_arr['sepa_owner']) : '';
		$aPost_arr['nn_sepaunique_id']     	 = isset($aPost_arr['sepa_unique_id']) ? trim($aPost_arr['sepa_unique_id']) : '';
		$_SESSION['novalnet']['nn_sepapanhash'] = $aPost_arr['nn_sepapanhash']  = isset($aPost_arr['sepa_pan_hash']) ? trim($aPost_arr['sepa_pan_hash']): '';
		$aPost_arr['nn_sepaibanconform']     = isset($aPost_arr['sepa_iban_conformed']) ? $aPost_arr['sepa_iban_conformed'] : '';
		$_SESSION['novalnet']['sepa_field_validator'] = $aPost_arr['sepa_field_validator']	= isset($aPost_arr['sepa_field_validator']) ? $aPost_arr['sepa_field_validator'] : '';
		$_SESSION['novalnet']['sepa_mandate_ref']	= $aPost_arr['sepa_mandate_ref']	= isset($aPost_arr['sepa_mandate_ref']) ? $aPost_arr['sepa_mandate_ref'] : '';
		$_SESSION['novalnet']['sepa_mandate_date']	= $aPost_arr['sepa_mandate_date']	= isset($aPost_arr['sepa_mandate_date']) ? $aPost_arr['sepa_mandate_date'] : '';
	}
  
    $lang_code 		= $GLOBALS['oSprache']->cISOSprache == 'ger' ? 'DE' : 'EN';
    $country_code 	= $_SESSION['Kunde']->cLand;
    $panhash 		= (($this->autoFillFields) ? $_SESSION['novalnet']['nn_sepapanhash'] : '');    
    $fldVdr		= (($this->autoFillFields) && !empty($panhash) ? $_SESSION['novalnet']['sepa_field_validator']:'');
    
    $customer_name 	= $_SESSION['Kunde']->cVorname.' '.$_SESSION['Kunde']->cNachname;
    $address		= $_SESSION['Kunde']->cHausnummer . ', ' . html_entity_decode( $_SESSION['Kunde']->cStrasse );
    $zip			= $_SESSION['Kunde']->cPLZ;
    $city			= $_SESSION['Kunde']->cOrt;
    $email			= $_SESSION['Kunde']->cMail;
    if (!$this->basicValidationOnhandleAdditional()) {
      $smarty->assign('sepaerror', true);
      return false;
    }
    $this->doCheckManualCheckLimit($amount);
    $manual_check_limit	=	$this->manual_check_limit;
    $product_id2		=	$this->productid_2;
    $tariff_id2			=	$this->traiff_id2;
    $mandate_ref  = $_SESSION['novalnet']['sepa_mandate_ref'];
	  $mandate_date = $_SESSION['novalnet']['sepa_mandate_date'];
	
	  $path   = "novalnet_sepa_form.php?lang_code=$lang_code&vendor_id=$this->vendorid&product_id=$this->productid";
    $path  .= "&auth_code=$this->authcode&payment_id=$payment_id&country=$country_code&fldVdr=$fldVdr&panhash=$panhash";
    $path  .= "&mandate_ref=$mandate_ref&mandate_date=$mandate_date&name=$customer_name&comp=$comp";
    $path  .= "&address=$address&zip=$zip&city=$city&email=$email";
 
	$smarty->assign('path', $path);
	
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
  $smarty->assign('data', $this->getCache());
  return false;
  }
 
  function handleNotification($order, $paymentHash, $args) {
    global $oPlugin;

    $amount = $this->amountConversion( $order );
    $this->doCheckManualCheckLimit( $amount ); 
    if($this->sepapayment_type == 'DIRECT_DEBIT_SEPA') {
		$this->changeOrderStatus($order);
	} 
	if($this->sepapayment_type == 'DIRECT_DEBIT_SEPA_SIGNED' && !empty($_SESSION['post_array']['sepa_mandate_ref']) && !empty($_SESSION['post_array']['sepa_mandate_date'])) {
		$this->changeOrderStatus($order);
	}
    $this->postBackCall( $this->payment_name, $_SESSION['novalnet']['success'], $order );
    $paymenthash = $this->generateHash( $order );
    header ("Location: " . gibShopURL() . "/bestellabschluss.php?i=" . $paymenthash);
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
