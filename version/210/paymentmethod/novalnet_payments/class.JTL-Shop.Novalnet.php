<?php
#########################################################
#                                                       #
#  Released under the GNU General Public License.       #
#  This free contribution made by request.              #
#  If you have found this script usefull a small        #
#  recommendation as well as a comment on merchant form #
#  would be greatly appreciated.                        #
#                                                       #
#  Script : class.JTL-Shop.Novalnet.php                 #
#                                                       #
#########################################################
include_once( PFAD_ROOT.PFAD_INCLUDES_MODULES . 'PaymentMethod.class.php' );
  if ($GLOBALS['oSprache']->cISOSprache == 'ger') {
    require_once( dirname(dirname(__FILE__)) . '/lang/de_DE.php' );
  }
  else {
    require_once( dirname(dirname(__FILE__)) . '/lang/en_GB.php' );
  }
class NovalnetGateway extends PaymentMethod {

  public $password_payments     = array('novalnet_bank', 'novalnet_ideal', 'novalnet_paypal', 'novalnet_safetypay');
  public $manuallimit_payments  = array('novalnet_elvde', 'novalnet_elvat', 'novalnet_cc', 'novalnet_sepa');
  /**
   * set the payment key
   *
   * @param none
   * @return string
   */
  function setPaymentKey() {
	 global $oPlugin;
	$key = array(
      'novalnet_elvde'        => 2,
      'novalnet_cc'           => 6,
      'novalnet_elvat'        => 8,
      'novalnet_telefon'      => 18,
      'novalnet_prepayment'   => 27,
      'novalnet_invoice'      => 27,
      'novalnet_bank'         => 33,
      'novalnet_paypal'       => 34,
      'novalnet_ideal'        => 49,
	  'novalnet_safetypay'	  => 54,
    );
    if ($this->sepapayment_type == 'DIRECT_DEBIT_SEPA_SIGNED') 
		$key['novalnet_sepa']	= '55';
	else
		$key['novalnet_sepa']	= '37';
		
    return $key[$this->payment_name];
  }
  /**
  * set the payment redirection URL
  *
  * @param none
  * @return string
  */
  function setPaymentUrl() {
    $url = array(
      'novalnet_prepayment'   => 'https://payport.novalnet.de/paygate.jsp',
      'novalnet_invoice'      => 'https://payport.novalnet.de/paygate.jsp',
      'novalnet_cc'           => 'https://payport.novalnet.de/paygate.jsp',
      'novalnet_elvde'        => 'https://payport.novalnet.de/paygate.jsp',
      'novalnet_elvat'        => 'https://payport.novalnet.de/paygate.jsp',
      'novalnet_paypal'       => 'https://payport.novalnet.de/paypal_payport',
      'novalnet_ideal'        => 'https://payport.novalnet.de/online_transfer_payport',
      'novalnet_bank'         => 'https://payport.novalnet.de/online_transfer_payport',
      'novalnet_telefon'      => 'https://payport.novalnet.de/paygate.jsp',
      'novalnet_safetypay'    => 'https://payport.novalnet.de/safetypay',
      'novalnet_sepa'    	  => 'https://payport.novalnet.de/paygate.jsp',
    );
  return trim( $url[$this->payment_name] );
  }
  /**
  * Assign the configuration values
  *
  * @param  none
  * @return none
  */
  function doAssignConfigVarsToMembers() { 
    global $oPlugin;	
    $this->vendorid   = trim( $oPlugin->oPluginEinstellungAssoc_arr['novalnet_vendorId'] );
    $this->productid  = trim( $oPlugin->oPluginEinstellungAssoc_arr['novalnet_productId'] );
    $this->authcode   = trim( $oPlugin->oPluginEinstellungAssoc_arr['novalnet_authCode'] );
    $this->tariffid   = trim( $oPlugin->oPluginEinstellungAssoc_arr['novalnet_tariffId'] );
    $this->proxy      = trim( $oPlugin->oPluginEinstellungAssoc_arr['novalnet_proxy'] );
    $this->sepapayment_type = trim( $oPlugin->oPluginEinstellungAssoc_arr['novalnet_sepa_payment_type'] );
    $this->novalnet_sepa_due_date = trim( $oPlugin->oPluginEinstellungAssoc_arr['novalnet_sepa_due_date'] );
    $this->referrerid = trim( $oPlugin->oPluginEinstellungAssoc_arr['novalnet_referrerid'] );
	$this->referenceone = trim( $oPlugin->oPluginEinstellungAssoc_arr[$this->payment_name . '_reference1'] );
    $this->referencetwo = trim( $oPlugin->oPluginEinstellungAssoc_arr[$this->payment_name . '_reference2'] );
    $this->testmode   = trim( $oPlugin->oPluginEinstellungAssoc_arr[$this->payment_name . '_testmode']);
    if($this->payment_name == 'novalnet_invoice') {
      $this->payment_duration = trim( $oPlugin->oPluginEinstellungAssoc_arr[$this->payment_name . '_duration']);
    }
    elseif (in_array($this->payment_name, $this->password_payments )) {
      $this->key_password = trim($oPlugin->oPluginEinstellungAssoc_arr['novalnet_password']);
      if ($this->payment_name == 'novalnet_paypal') {
        $this->api_username   = trim($oPlugin->oPluginEinstellungAssoc_arr[$this->payment_name . '_apiUser']);
        $this->api_password   = trim($oPlugin->oPluginEinstellungAssoc_arr[$this->payment_name . '_apiPass']);
        $this->api_signature  = trim($oPlugin->oPluginEinstellungAssoc_arr[$this->payment_name . '_apiSign']);
      }
    }
    elseif (in_array($this->payment_name, $this->manuallimit_payments)) {
      $this->manual_check_limit = trim($oPlugin->oPluginEinstellungAssoc_arr[$this->payment_name . '_manualCheckLimit']);
      $this->productid_2        = trim($oPlugin->oPluginEinstellungAssoc_arr[$this->payment_name . '_productId2']);
      $this->tariffid_2         = trim($oPlugin->oPluginEinstellungAssoc_arr[$this->payment_name . '_tariffId2']);
      if ($this->payment_name == 'novalnet_elvde') {
        $this->acdc               = trim($oPlugin->oPluginEinstellungAssoc_arr[$this->payment_name . '_acdc']);
      }
    }
    if (in_array($this->payment_name, $this->manuallimit_payments)) {
		$this->autoFillFields       = trim($oPlugin->oPluginEinstellungAssoc_arr[$this->payment_name . '_autorefill']);
		if ($this->payment_name == 'novalnet_cc') {
			$this->novalnet_cc3d_mode   = trim($oPlugin->oPluginEinstellungAssoc_arr[$this->payment_name . '_3d_activemode']);
		}
	  }
	  
  }
  /**
  * set order type
  *
  * @param  none
  * @return boolean
  */
  function returnOrderType() {
    $pos = strpos(basename( $_SERVER['REQUEST_URI'] ), 'bestellab_again.php' );
    return ($pos === false ? false : true);
  }
  /**
  * validation for basic parameters
  *
  * @order_update  boolean
  * @return none
  */
  function basicValidation($order_update) {
    $payment_manual_limit = array('novalnet_elvde', 'novalnet_elvat', 'novalnet_cc', 'novalnet_sepa');
    $payment_password = array('novalnet_paypal', 'novalnet_ideal', 'novalnet_bank', 'novalnet_safetypay');

    if ((isset($this->vendorid) && !$this->isDigits($this->vendorid)) || (isset($this->productid) && !$this->isDigits($this->productid)) || (isset( $this->authcode ) && empty( $this->authcode )) || (isset( $this->tariffid ) && !$this->isDigits($this->tariffid))) {
      $_SESSION['novalnet']['error'] = NOVALNET_BASIC_ERROR_MSG;
    }
    elseif (in_array($this->payment_name, $payment_password)) {
      $_SESSION['novalnet']['error'] = (empty( $this->key_password ) ? NOVALNET_BASIC_ERROR_MSG : '');
    }
    elseif (in_array($this->payment_name, $payment_manual_limit)) {
        if((int)$this->manual_check_limit ) {
          if ((isset($this->productid_2) && !$this->isDigits($this->productid_2))
          || (isset( $this->tariffid_2 ) && !$this->isDigits($this->tariffid_2))) {
          $_SESSION['novalnet']['error'] = NOVALNET_MANUALCHECK_ERROR_MSG;
          }
        }
    }
    if ($this->payment_name == 'novalnet_paypal') {
      if (empty($this->api_username) || empty($this->api_password) || empty($this->api_signature)) {
        $_SESSION['novalnet']['error'] = NOVALNET_BASIC_ERROR_MSG;
      }
    }
    if (isset($_SESSION['novalnet']['error']) && !empty($_SESSION['novalnet']['error'])) {
      $this->returnOnError($order_update);
    }
    if ($this->payment_name == 'novalnet_sepa' && $this->sepapayment_type == 'DIRECT_DEBIT_SEPA') {
		if((!empty($this->novalnet_sepa_due_date) && $this->novalnet_sepa_due_date < 7) || ( $this->novalnet_sepa_due_date == '0' && strlen($this->novalnet_sepa_due_date) > 0 ))
			$_SESSION['novalnet']['error'] = NOVALNET_SEPA_DUE_DATE_ERROR_MSG;
	}
	
  }
  /**
  * convert amount to cents
  *
  * @order  array
  * @return double
  */
  function amountConversion($order) {
    $amount = number_format( $order->fGesamtsummeKundenwaehrung, 2, '.', '' );
    $amount = sprintf( '%0.2f', $amount );
    $amount = preg_replace( '/^0+/', '', $amount );
    $amount = str_replace( '.', '', $amount );
  return $amount;
  }
  /**
  * set order type & order number
  *
  * @order_update  boolean
  * @Zahlung_vor_Bestell  array
  * @order  array
  * @order_no  string
  * @return string/none
  */
  function setOrderNumber($order_update, $Zahlung_vor_Bestell, $order, &$order_no) {
    if ($_SESSION['Zahlungsart']->nWaehrendBestellung == 0 && $order_update == false || ($order_update == true && $Zahlung_vor_Bestell->nWaehrendBestellung != '')) {
      $_SESSION['novalnet']['nWaehrendBestellung'] = 'Nein';
      $order_no           = $order->cBestellNr;
      if ($order_update == true && $Zahlung_vor_Bestell->nWaehrendBestellung == 0) {
        $_SESSION['novalnet']['reorder'] = 'Ja';
      }
    }
  }
  /**
  * set return urls for redirection payment
  *
  * @order_update  boolean
  * @Zahlung_vor_Bestell  array
  * @order  array
  * @params  array
  * @return array
  */
  function setReturnUrls(&$params, $order_update, $Zahlung_vor_Bestell, $order) { 
    $paymentHash = $this->generateHash($order);
    if (($_SESSION["Zahlungsart"]->nWaehrendBestellung == 0 && $order_update == false ) || ($order_update == true &&$Zahlung_vor_Bestell->nWaehrendBestellung != '')) {
      $_SESSION['novalnet']['nWaehrendBestellung'] = 'Nein';
      $params['cReturnURL']   = gibShopURL() . "/bestellabschluss.php?i=" . $paymentHash;
      $params['order_no']     = $order->cBestellNr;
      $params['cFailureURL']  = gibShopURL() . '/novalnet_return.php';
    }
    else {
       $params['cReturnURL']   = $this->getNotificationURL($paymentHash) . '&sh=' . $paymentHash;
    }
  }
  /* find the string position */
  function validateString($string, $substring) {
    $pos = strpos( $string, $substring );
    return ($pos === false ? false : true);
  }
  /**
  * validate user account details
  *
  * @aPost_arr  array
  * @return boolean
  */
  function validateAccountDetails($aPost_arr) { 
    global $smarty;

    if ($this->payment_name == 'novalnet_elvde' || $this->payment_name == 'novalnet_elvat') {
      if (strlen($aPost_arr['nn_holdername']) < 3 || preg_match('/[#%\^<>@$=*!]/', $aPost_arr['nn_holdername']) || !preg_match('/^[0-9]+$/', $aPost_arr['nn_accountnumber']) || empty($aPost_arr['nn_accountnumber']) || !preg_match('/^[0-9]+$/', $aPost_arr['nn_bankcode']) || empty ($aPost_arr['nn_bankcode'])) {
        $error_tmp = NOVALNET_ELVATDESEPA_ACCOUNT_ERROR_MSG;
      }
      elseif ($this->acdc == 1 && $this->payment_name == 'novalnet_elvde') {
        if (!$aPost_arr['nn_acdc']) {
            $error_tmp = NOVALNET_ELVDE_ACDC_ERROR_MSG;
        }
      }
    }
    elseif ($this->payment_name == 'novalnet_cc') {
      if (!isset($aPost_arr['nn_cardno_id']) || empty($aPost_arr['nn_cardno_id'])
	  || !isset($aPost_arr['nn_unique_id']) || empty($aPost_arr['nn_unique_id'])
	  || strlen($aPost_arr['nn_holdername']) < 2 || preg_match('/[#%\^<>@$=*!]/', $aPost_arr['nn_holdername'])
	  || !preg_match('/^[0-9]+$/', $aPost_arr['nn_cvvnumber'])
	  || $aPost_arr['nn_expyear'] == date('Y') && $aPost_arr['nn_expmonth'] < date('m')
	  || $aPost_arr['nn_type'] == '' ) {
        $error_tmp = NOVALNET_CC3DCC_ACCOUNT_ERROR_MSG;
      }
    }
	elseif($this->payment_name == 'novalnet_sepa') { 
		if(preg_match('/[#%\^<>@$=*!]/', $aPost_arr['nn_sepaowner'])) {
			$error_tmp = NOVALNET_ELVATDESEPA_ACCOUNT_ERROR_MSG;
		}		
		elseif(!isset($aPost_arr['nn_sepaibanconform']) || empty($aPost_arr['nn_sepaibanconform'])) { 
			$error_tmp = NOVALNET_SEPA_ACCOUNT_ERROR_MSG;
		} 
		elseif (!isset($aPost_arr['nn_sepaunique_id']) || empty($aPost_arr['nn_sepaunique_id'])
		|| !isset($aPost_arr['nn_sepapanhash']) || empty($aPost_arr['nn_sepapanhash'])) { 
			$error_tmp = NOVALNET_ELVATDESEPA_ACCOUNT_ERROR_MSG;
		}
	 }

    if (isset($error_tmp) && $error_tmp != '') {
      $smarty->assign('error', true);
      $smarty->assign('error_desc', $error_tmp);
      return false;
    }

  return true;
  }

  /**
  * validation for basic params
  *
  * @param none
  * @return none
  */
  function basicValidationOnhandleAdditional() {
     global $smarty;
   if ((isset($this->vendorid) && !$this->isDigits($this->vendorid)) || (isset($this->productid) && !$this->isDigits($this->productid)) || (isset( $this->authcode ) && empty( $this->authcode )) || (isset( $this->tariffid ) && !$this->isDigits($this->tariffid))) {
      $_SESSION['novalnet']['error'] = NOVALNET_BASIC_ERROR_MSG;
    }
     elseif($this->manual_check_limit) { 
          if (is_numeric($this->manual_check_limit)) { 
            if (((int)$this->manual_check_limit > 0) && (!is_numeric($this->productid_2)||!is_numeric($this->tariffid_2))) {  
                  $_SESSION['novalnet']['error'] = NOVALNET_MANUALCHECK_ERROR_MSG;
              } 
            } else {  
                  $_SESSION['novalnet']['error'] = NOVALNET_MANUALCHECK_ERROR_MSG;
              }
      }
 
     if (isset($_SESSION['novalnet']['error']) && !empty($_SESSION['novalnet']['error'])) {
      $smarty->assign('error', true);
      $smarty->assign('error_desc', $_SESSION['novalnet']['error']);
       
      return false;
    }
    return true;
  }
 
  /**
  * build basic parameters for server
  *
  * @data array
  * @order array
  * @uniqueid string
  * @return array
  */
  function bulidBasicParams(&$data, $order, $uniqueid = NULL) {
    $amount     = $this->amountConversion( $order );
	if ($this->payment_name == 'novalnet_safetypay') {
	   $data['vendor_id'] 		 = $this->vendorid;    	
       $data['vendor_authcode']  = $this->encode( $this->authcode );
       $data['product_id'] 		 = $this->encode( $this->productid );
	   $data['tariff_id'] 		 = $this->encode( $this->tariffid);
	   $data['amount']       	 = $this->encode( $amount );
       $data['test_mode']    	 = $this->encode( $this->testmode );
       $data['uniqid']       	 = $this->encode( $uniqueid );
	   $data['implementation']   = 'PHP_PCI';
       $data['hash']         	 = $this->hash( array(
                                      'authcode'    => $data['vendor_authcode'],
                                      'product_id'  => $data['product_id'],
                                      'tariff'      => $data['tariff_id'],
                                      'amount'      => $data['amount'],
                                      'test_mode'   => $data['test_mode'],
                                      'uniqid'      => $data['uniqid'],
                                      ));
      $data['key']          = $this->setPaymentKey();
	
	} elseif (in_array($this->payment_name, $this->password_payments)) {
      $data['vendor']       = $this->vendorid;
      $data['auth_code']    = $this->encode( $this->authcode );
      $data['product']      = $this->encode( $this->productid );
      $data['tariff']       = $this->encode( $this->tariffid);
      $data['amount']       = $this->encode( $amount );
      $data['test_mode']    = $this->encode( $this->testmode );
      $data['uniqid']       = $this->encode( $uniqueid );
      $data['hash']         = $this->hash( array(
                                      'authcode'    => $data['auth_code'],
                                      'product_id'  => $data['product'],
                                      'tariff'      => $data['tariff'],
                                      'amount'      => $data['amount'],
                                      'test_mode'   => $data['test_mode'],
                                      'uniqid'      => $data['uniqid'],
                                      ));
      $data['key']          = $this->setPaymentKey();
    }
    else {
      $data['vendor']     = $this->vendorid;
      $data['auth_code']  = $this->authcode;
      $data['product']    = $this->productid;
      $data['tariff']     = $this->tariffid;
      $data['test_mode']  = $this->testmode;

      if($this->payment_name == 'novalnet_sepa' && $this->sepapayment_type == 'DIRECT_DEBIT_SEPA_SIGNED')
			$data['key'] = $this->additional_key;
		else
			$data['key'] = $this->setPaymentKey();
      $data['amount']     = $amount;
    }

  }
  
  /**
  * build common parameters for server
  *
  * @data array
  * @order array
  * @customer array
  * @return array
  */
  function buildCommonParams(&$data, $order, $customer) { 
    $language       = $GLOBALS['oSprache']->cISOSprache == 'ger' ? 'DE' : 'EN';
    $jtl_version    = $GLOBALS['DB']->executeQuery( "select nVersion from tversion",1 );
    $street         = utf8_encode( $customer->cHausnummer . ', ' . html_entity_decode( $customer->cStrasse ));
    $street         = $jtl_version->nVersion == '312' ? utf8_encode( html_entity_decode( $customer->cStrasse )) : $street;
    $customer_no    = $_SESSION['Kunde']->nRegistriert == '0' ? 'Guest' : $customer->kKunde;
    $ipAddress      = $this->getRealIpAddr();

    $data['currency']           = $order->Waehrung->cISO;
    $data['remote_ip']          = (($ipAddress == '::1') ? '127.0.0.1' : $ipAddress);
    $data['first_name']         = ( $customer->cVorname ? $customer->cVorname  : '' );
    $data['last_name']          =  $customer->cNachname ;
    $data['gender']             = 'u';
    $data['email']              = $customer->cMail;
    $data['street']             = utf8_decode($street);
    $data['search_in_street']   = 1;
    $data['city']               =  $customer->cOrt;
    $data['zip']                = $customer->cPLZ;
    $data['language']           = $language;
    $data['lang']               = $language;
    $data['country_code']       = $customer->cLand;
    $data['tel']                = $customer->cTel;
    $data['customer_no']        = $customer_no;
    $data['use_utf8']           = 1;
    $data['system_name']        = 'JTLShop - ';
    $data['system_version']     = $jtl_version->nVersion . '-NN_2.1.0';
    $data['system_url']         = gibShopURL();
    $data['system_ip']          = $_SERVER['SERVER_ADDR'];

  }
  /**
  * build additional parameters for server
  *
  * @data array
  * @payment_name string
  * @params array
  * @return array
  */
  function additionalParams(&$data, $params) {
    $data['input1']               = 'order_comments';
    $data['inputval1']            = $params['order_comments'];
    if(!empty($this->referrerid)) {
		$data['referrer_id']  = strip_tags($this->referrerid);
	}
    if ($this->payment_name == 'novalnet_cc') {
      $data['cc_type']            =  $_SESSION['post_array']['nn_type'];
      $data['cc_holder']          =  $_SESSION['post_array']['nn_holdername'];
      $data['cc_no']              =  '';
      $data['cc_exp_month']       =  $_SESSION['post_array']['nn_expmonth'];
      $data['cc_exp_year']        =  $_SESSION['post_array']['nn_expyear'];
      $data['cc_cvc2']            =  $_SESSION['post_array']['nn_cvvnumber'];
      $data['pan_hash']           =  $_SESSION['post_array']['nn_cardno_id'];
      $data['unique_id']          =  $_SESSION['post_array']['nn_unique_id'];
      if ($this->novalnet_cc3d_mode) {
		$data['session']              = session_id();
		$data['return_url']           = $params['cReturnURL'];
		$data['return_method']        = 'POST';
		$data['error_return_url']     = $params['cFailureURL'];
		$data['error_return_method']  = 'POST';
		$data['paymentname']          = $this->payment_name;
	  }
    }
    elseif($this->payment_name == 'novalnet_prepayment' || $this->payment_name == 'novalnet_invoice') {
      $data['invoice_type']       = 'PREPAYMENT';
      if ($this->payment_name == 'novalnet_invoice') {
        $data['invoice_type']     = 'INVOICE';
      }
    }
    elseif (in_array($this->payment_name, $this->password_payments)) {
      $data['session']              = session_id();
      $data['return_url']           = $params['cReturnURL'];
      $data['return_method']        = 'POST';
      $data['error_return_url']     = $params['cFailureURL'];
      $data['error_return_method']  = 'POST';
      $data['paymentname']          = $this->payment_name;
      $data['user_variable_0']      = gibShopURL();
      $data['order_comments']       = $params['order_comments'];
      if ($this->payment_name == 'novalnet_paypal') {
            $data['api_user']       = $this->encode($this->api_username);
            $data['api_pw']         = $this->encode($this->api_password);
            $data['api_signature']  = $this->encode($this->api_signature);
      }
    }
    elseif ($this->payment_name == 'novalnet_elvde' || $this->payment_name == 'novalnet_elvat') {
      $data['bank_code']            = trim($_SESSION['post_array']['nn_bankcode']);
      $data['bank_account']         = trim($_SESSION['post_array']['nn_accountnumber']);
      $data['bank_account_holder']  = html_entity_decode(trim($_SESSION['post_array']['nn_holdername']));
      if($this->payment_name == 'novalnet_elvde') {
        if (isset($_SESSION['post_array']['nn_acdc']) && $_SESSION['post_array']['nn_acdc'] == 1) {
          $data['acdc'] = 1;
        }
      }
    }
    elseif ($this->payment_name == 'novalnet_sepa') {
		$data['sepa_unique_id']			= $_SESSION['post_array']['nn_sepaunique_id'];
		$data['sepa_hash']				= $_SESSION['post_array']['nn_sepapanhash'];
		$data['bank_account_holder']	= $_SESSION['post_array']['sepa_owner'];
		$data['bank_account']			= '';
		$data['bank_code']				= '';
		$data['bic']					= '';
		$data['iban']					= '';
		$data['iban_bic_confirmed']         = $_SESSION['post_array']['nn_sepaibanconform'];
		
		
		if($this->sepapayment_type == 'DIRECT_DEBIT_SEPA') {
			$data['sepa_due_date'] = (preg_match('/^[0-9]+$/',$this->novalnet_sepa_due_date) && $this->novalnet_sepa_due_date > 6)? date("Y-m-d",mktime(0,0,0,date("m"),(date("d")+$this->novalnet_sepa_due_date),date("Y"))) : date("Y-m-d",mktime(0,0,0,date("m"),date("d")+'7',date("Y")));
		}
		elseif($this->sepapayment_type == 'DIRECT_DEBIT_SEPA_SIGNED') {
			if ( !empty($_SESSION['post_array']['sepa_mandate_ref']) && !empty($_SESSION['post_array']['sepa_mandate_date']) ) {
				$data['mandate_ref']			= $_SESSION['post_array']['sepa_mandate_ref'];
				$data['mandate_signature_date']	= $_SESSION['post_array']['sepa_mandate_date'];
				$data['mandate_present']		= '1';
			}
			else {
			$data['mandate_present']	= '0';
			}
		}
	} else { }
   if( !empty($this->referenceone)) {
		$data['input2']		= 'reference1';
		$data['inputval2']	= strip_tags($this->referenceone);
	}
	if(!empty($this->referencetwo)) {
		$data['input3']		= 'reference2';
		$data['inputval3']	= strip_tags($this->referencetwo);
	}

  }
  /**
  * build the novalnet order comments
  *
  * @parsed array
  * @payment_name string
  * @order array
  * @return string
  */
  function updateOrderComments($parsed, $order, $payment_name) {
    $comments = '';
    $br = "\n";
      if (in_array($this->payment_name, $this->password_payments)) { 
        $parsed['test_mode'] = $this->decode($parsed['test_mode']);
      }
      if ((isset( $parsed['test_mode'] ) && trim( $parsed['test_mode'] ) == 1) || $this->testmode == 1) { 
          $comments .= $br . NOVALNET_TESTORDER_MSG . $br;
      }
      if ($this->payment_name == 'novalnet_prepayment' || $this->payment_name == 'novalnet_invoice') {
        $comments .= ($this->payment_name == 'novalnet_prepayment') ? NOVALNET_PREPAYMENT_NAME . $br : NOVALNET_INVOICE_NAME . $br;
        $comments .= NOVALNET_PREPAYMENT_TID_LABEL . $parsed['tid'] . $br . $br;
	  if ( $GLOBALS['oSprache']->cISOSprache == 'ger' ) {
			$comments .= utf8_decode('Bitte überweisen Sie den Betrag mit der folgenden Information an unseren Zahlungsdienstleister Novalnet').$br;
		} else {
			$comments .= NOVALNET_PREPAYMENT_COMMENT_HEAD.$br;
		}		
        $$comments .= ($this->payment_name == 'novalnet_invoice' && $this->payment_duration != '' && $this->isDigits($this->payment_duration)) ? NOVALNET_INVOICE_DUE_DATE . date("d.m.Y",mktime(0,0,0,date("m"),(date("d") + $this->payment_duration),date("Y"))) . $br : '';
        $comments .= NOVALNET_PREPAYMENT_HOLDER_LABEL . $br;
        $comments .= NOVALNET_PREPAYMENT_ACCNO_LABEL . $parsed['invoice_account'] . $br;
        $comments .= NOVALNET_PREPAYMENT_BANKCODE_LABEL . $parsed['invoice_bankcode'] . $br;
        $comments .= NOVALNET_PREPAYMENT_BANKNAME_LABEL . $parsed['invoice_bankname'] . ' ' . trim( $parsed['invoice_bankplace'] ) . $br;
        $comments .= NOVALNET_PREPAYMENT_AMOUNT_LABEL . number_format( $parsed['amount'], 2, ',', '.' ) . ' ' . $order->Waehrung->cISO . $br;
        $comments .= NOVALNET_PREPAYMENT_REFRENCE_LABEL . $parsed['tid'] . $br . $br;
        if ( $GLOBALS['oSprache']->cISOSprache == 'ger' ) {
          $comments .= utf8_decode('Nur bei Auslandsüberweisungen:').$br;
        }else{
          $comments .=  NOVALNET_PREPAYMENT_NOTE_HEAD.$br;
        }
        $comments .= NOVALNET_PREPAYMENT_IBAN_LABEL . $parsed['invoice_iban'] . $br;
        $comments .= NOVALNET_PREPAYMENT_SWIFT_LABEL . $parsed['invoice_bic'] . $br;
      }
      else {
	    $comments .= NOVALNET_PREPAYMENT_TID_LABEL . trim($parsed['tid']) . $br ;
      }
      if ($this->sepapayment_type == 'DIRECT_DEBIT_SEPA_SIGNED' && $this->payment_name == 'novalnet_sepa' ) { 
		if ( empty($_SESSION['post_array'] ['sepa_mandate_ref']) && empty($_SESSION['post_array'] ['sepa_mandate_date']) ) {
		  if (isset($parsed['mandate_url']) && trim($parsed['mandate_url']) !='' ) {
			  if (strpos($parsed['mandate_url'], "?") !== false) {
				$url          = explode('?', $parsed['mandate_url']);
				$mandate_ref  = (isset($parsed['mandate_ref']) && trim($parsed['mandate_ref']) !='') ? ('&mandate_ref=' . $data['mandate_ref']) :('&mandate_ref=NN' . $this->vendorid . '-' . trim($parsed['tid']));
				$mandat_url = $url[0] . '?vendor_id='. $this->vendorid . '&tid=' . trim($parsed['tid']) . $mandate_ref;
				$comments      .= NOVALNET_SEPA_MANDATE_URL . ' : <a href= \'' . $mandat_url .'\' target=\'_blank\'>' . NOVALNET_SEPA_MANDATE_CLICK .'</a>';
			 }
		  }
          else {
			  $mandate_ref  = (isset($parsed['mandate_ref']) && trim($parsed['mandate_ref']) !='') ? ('&mandate_ref=' . $parsed['mandate_ref']) :
			  ('&mandate_ref=NN' . $this->vendorid . '-' . trim($parsed['tid']));
			  $url = 'https://payport.novalnet.de/sepa/mandate_pdf_generate.jsp?vendor_id=' . $this->vendorid . '&tid=' . trim($parsed['tid']) . $mandate_ref;
			  $comments .= NOVALNET_SEPA_MANDATE_URL . ' : <a href = \''.$url .'\' target=\'_blank\'>' . NOVALNET_SEPA_MANDATE_CLICK . '</a>';
		  }
		}
			$ins = "INSERT INTO `xplugin_novalnetag_tnovalnet` Values ('','','".trim($parsed['tid'])."','".trim($parsed['nc_no'])."')";
			$GLOBALS['DB']->executeQuery($ins);
		}
			
      return $comments;
  }
  /**
  * update the order comments to databse
  *
  * @order array
  * @reference string
  * @order_comments string
  * @return none
  */
  function addReferenceToComment($order, $reference, $order_comments){
    global $DB;
    $comments = '';
    if ($order_comments == 'TRUE') {
      if (!empty( $_SESSION['kommentar']) ) {
        $comments = $_SESSION['kommentar'];
      }
      elseif (!empty( $_SESSION['cPost_arr']['kommentar'] )) {
        $comments =  $_SESSION['cPost_arr']['kommentar'];
      }
    }
 
    $SQL = 'UPDATE tbestellung SET '
         . '    cKommentar = CONCAT(cKommentar, "' . $comments . '","' . $reference . '") '
         . 'WHERE kBestellung = ' . intval( $order->kBestellung ); 
    $DB->executeQuery( $SQL, 4 );
 
    unset( $_SESSION['kommentar'] );
  }
  /**
  * perform postback call to novalnet server
  *
  * @payment_name string
  * @parsed array
  * @order array
  * @return none
  */
  function postBackCall($payment_name, $parsed, $order) {  
	global $DB;
    if (isset( $parsed['tid'] ) && !empty( $parsed['tid'] )) {
      $post_data = array (
          'vendor'      => trim($this->vendorid),
          'product'     => trim($this->productid),
          'tariff'      => trim($this->tariffid),
          'auth_code'   => trim($this->authcode),
          'key'         => trim($this->setPaymentKey()),
          'status'      => 100,
          'tid'         => trim($parsed['tid']),
          'order_no'    => trim($order->cBestellNr),
          );
      if ($payment_name == 'novalnet_prepayment' || $payment_name == 'novalnet_invoice') {
        $post_data['invoice_ref'] = 'BNR-' . $this->productid . '-' . $order->cBestellNr;
      }

      if ($this->payment_name == 'novalnet_sepa' ) {
		$SQL = 'UPDATE xplugin_novalnetag_tnovalnet SET nnorderid = "' . $order->cBestellNr . '" WHERE nntid = ' . trim($parsed['tid']);
		$DB->executeQuery( $SQL);
	  }

      if ($this->validatePostBackData($post_data)) {
        if (!array_search('', $post_data)) {
          $post_data  = http_build_query($post_data, '', '&');
          $url        = 'https://payport.novalnet.de/paygate.jsp';
          $response   = $this->novalnet_debit_call( $post_data, $url );

        }
      }
      unset( $_SESSION['novalnet'] );
    }
  }
  function validatePostBackData($post_data) {
    if (!$this->isDigits($post_data['vendor']) || !$this->isDigits($post_data['product']) || !$this->isDigits($post_data['tariff']) || !$this->isDigits($post_data['key']) || empty($post_data['tid'])){
      return false;
    }
    return true;
  }
  /**
  * finalize the order
  *
  * @paymentHash string
  * @args array
  * @response array
  * @order array
  * @return boolean
  */
 function verifyNotification_first($order, $paymentHash, $args, $response) { 
    global $DB, $cEditZahlungHinweis;
    $redirection_payments = array('novalnet_bank', 'novalnet_ideal', 'novalnet_paypal', 'novalnet_safetypay');
    if (in_array($response['paymentname'], $redirection_payments)) { 
      if ($response['status'] && $response['status'] == 100 && $response['hash2']  ) {
        if (!$this->checkHash( $response )) {
         $error_message = NOVALNET_CHECKHASH_ERROR_MSG;
         $_SESSION['novalnet']['error'] = utf8_decode($response['status_text']) . ' - ' .$error_message;
         $cEditZahlungHinweis = $response['status_text'] . ' - ' . $error_message;
         return false;
        }
      }
      elseif ($response['status'] && $response['status'] == 90 && $response['hash2'] && $response['paymentname'] == 'novalnet_paypal') {
        if (!$this->checkHash( $response )) { 
         $error_message = NOVALNET_CHECKHASH_ERROR_MSG;
         $_SESSION['novalnet']['error'] = utf8_decode($response['status_text']) . ' - ' .$error_message;
         $cEditZahlungHinweis = $response['status_text'] . ' - ' . $error_message;
         return false;
        }
      }
    }
    if ( $response['status'] && $response['status'] == 100 ) {
        $comments       = $this->updateOrderComments( $response, $order, $this->payment_name );
        $_POST["kommentar"] = isset($_SESSION['kommentar']) ? $comments ."<br>". $_SESSION['kommentar'] : $comments;
        unset($_SESSION['kommentar']);
       return true;
    }
    elseif ($response['status'] && $response['status'] == 90 &&  $response['paymentname'] == 'novalnet_paypal') {
        $comments       = $this->updateOrderComments( $response, $order, $this->payment_name );
        $_POST["kommentar"] = isset($_SESSION['kommentar']) ? $comments ."<br>". $_SESSION['kommentar'] : $comments;
        unset($_SESSION['kommentar']);
       return true;
    }
    else {
       $_SESSION['novalnet']['error'] = utf8_decode($response['status_text']);
       $cEditZahlungHinweis = utf8_decode($response['status_text']);
     return false;
    }
  }
  /**
  * unset sessions
  *
  * @param none
  * @return none
  */
  function unsetSesions() {
    if ($_SESSION['Kunde']->nRegistriert === 0 && $_SESSION['Zahlungsart']->nWaehrendBestellung == 0) {
      unset($_SESSION['Kunde']);
    }
    if ($_SESSION['Kunde']->nRegistriert != 0) {
      $GLOBALS['DB']->executeQuery("delete wpp from twarenkorbperspos as wpp left join twarenkorbpers as wp on wpp.kWarenkorbPers= wp.kWarenkorbPers  where wp.kKunde='" . $customer->kKunde . "'", 4);
    }
    unset( $_SESSION['Warenkorb'] );
    if (!empty( $_SESSION['Kupon'] )) {
        unset( $_SESSION['Kupon'] );
    }
  }
  /**
  * check order status
  *
  * @order array
  * @payment_type string
  * @return array
  */
  function checkOrderOnUpdate($order, $payment_type = NULL) {
    $Zahlung_vor_Bestell = $GLOBALS['DB']->executeQuery("select nWaehrendBestellung, nMailSenden from tzahlungsart where cModulId LIKE '%$payment_type%'", 1);
    if ($payment_type != '') {
      $Bestellung_Vorauskasse = $GLOBALS['DB']->executeQuery("select cKommentar from tbestellung where cBestellNr='".$order->cBestellNr."'", 1);
      $search_term_array = array ('Novalnet AG', 'TID :', 'Reference : TID', 'Verwendungszweck : TID', 'Novalnet Transaction ID', 'Novalnet Transaktions-ID');
      foreach ($search_term_array as $search_term) {
        if ($this->validateString($Bestellung_Vorauskasse->cKommentar, $search_term)) {
          $_SESSION['novalnet']['error'] = NOVALNET_UPDATE_SUCESSORDER_ERRORMSG;
          header('Location:' . URL_SHOP . '/novalnet_return.php');
          exit;
        }
      }
    }
  return $Zahlung_vor_Bestell;
  }

  /* set error message */
  function setError() {
    global $hinweis;

    if ($this->payment_name == 'novalnet_telefon' && isset( $_SESSION['tele']['error'] ) && !empty( $_SESSION['tele']['error'] )) {
        $hinweis =  $_SESSION['tele']['error'];
        unset( $_SESSION['tele']['error'] );
        unset( $_SESSION['novalnet'] );
    }
    elseif (isset( $_SESSION['novalnet']['error'] ) && !empty( $_SESSION['novalnet']['error'] )) {
       $hinweis =  $_SESSION['novalnet']['error'];
       unset( $_SESSION['novalnet']['error'] );

    }
  }
  /**
  * encode the data
  *
  * @data string/double
  * @return string
  */
  function encode($data) {
    $data = trim($data);
    if ($data == '') return'Error: no data';
    if (!function_exists('base64_encode') or !function_exists('pack') or !function_exists('crc32')) {
      return'Error: func n/a';
    }
    try {
      $crc = sprintf('%u', crc32($data));# %u is a must for ccrc32 returns a signed value
      $data = $crc."|".$data;
      $data = bin2hex($data.$this->key_password);
      $data = strrev(base64_encode($data));
    }
    catch (Exception $e) {
      echo('Error: '.$e);
    }
    return $data;
  }
  /**
  * generate hash
  *
  * @h array
  * @return string
  */
  function hash($h) {
    if (!$h)
      return'Error: no data';
    if (!function_exists('md5')) {
      return'Error: func n/a';
    }
    return md5( $h['authcode'] . $h['product_id'] . $h['tariff'] . $h['amount'] . $h['test_mode'] . $h['uniqid'] . strrev($this->key_password));
  }
  /**
  * decode data
  *
  * @data string/boolean
  * @return string
  */
   function decode($data) {
    $data = trim($data);
    if ($data == '') {
      return'Error: no data';
    }
    if (!function_exists('base64_decode') or !function_exists('pack') or !function_exists('crc32')) {
      return'Error: func n/a';
    }
    try {
      $data = base64_decode(strrev($data));
      $data = pack("H".strlen($data), $data);
      $data = substr($data, 0, stripos($data, $this->key_password));
      $pos  = strpos($data, "|");
      if ($pos === false){
        return("Error: CKSum not found!");
      }
      $crc    = substr($data, 0, $pos);
      $value  = trim(substr($data, $pos+1));
      if ($crc !=  sprintf('%u', crc32($value))){
        return("Error; CKSum invalid!");
      }
      return $value;
    }
    catch (Exception $e) {
      echo('Error: '.$e);
    }
  }
  /**
  * check hash from response
  *
  * @request array
  * @return boolean
  */
  function checkHash($request) {
    if (!$request) return false; #'Error: no data';
    $h['authcode']    = $request['auth_code'];#encoded
    $h['product_id']  = $request['product'];#encoded
    $h['tariff']      = $request['tariff'];#encoded
    $h['amount']      = $request['amount'];#encoded
    $h['test_mode']   = $request['test_mode'];#encoded
    $h['uniqid']      = $request['uniqid'];#encoded
 
    if ($request['hash2'] != $this->hash($h)){
      return false;
    }
    return true;
  }
  /**
  * set order status
  *
  * @order array
  * @return none
  */
  function changeOrderStatus($order) {
    $incomingPayment->fBetrag = $order->fGesamtsummeKundenwaehrung;
    $incomingPayment->cISO = $order->Waehrung->cISO;
    $this->addIncomingPayment( $order, $incomingPayment );
    $this->setOrderStatusToPaid($order);
  }
  /**
  * check & assign manual limit
  *
  * @amount double
  * @return none
  */
  function doCheckManualCheckLimit($amount) {
    $this->manual_check_limit = (int)$this->manual_check_limit;
    if($this->manual_check_limit && $amount >= $this->manual_check_limit){
      if($this->productid_2 != '' && $this->tariffid_2 != '') {
        $this->productid  = $this->productid_2;
        $this->tariffid   = $this->tariffid_2;
      }
    }
  }

  /**
  * build redirection form
  *
  * @data array
  * @return string
  */
  function buildRedirectionForm($data) 
  {
	if ($this->payment_name != 'novalnet_cc')  {
		$form_action_url = $this->setPaymentUrl();
	} else {
		$form_action_url = 'https://payport.novalnet.de/global_pci_payport';
	}
    $frmData = '<form name="frmnovalnet" method="post" action="' . $form_action_url . '">';
    $frmEnd  = NOVALNET_REDIRECTION_MSG;
    $js      = '<script>document.forms.frmnovalnet.submit();</script>';
    foreach( $data as $k => $v ) {
      $frmData .= '<input type="hidden" name="' . $k . '" value="' . $v . '" />' . "\n";
    }
    return $frmData . $frmEnd . $js;
  }
  /**
  * set order to complete
  *
  * @response array
  * @return none
  */
  function orderComplete($response) {

    $order = $_SESSION['novalnet']['order'];
    unset($_SESSION['novalnet']['nWaehrendBestellung']);
    if (($response['hash2'] && $response['status'] == 100) || ($response['hash2'] && $response['status'] == 90 && $response['paymentname'] == 'novalnet_paypal') || ($response['status'] == 100 && ($response['paymentname'] == 'novalnet_cc' && $this->novalnet_cc3d_mode ))) { 
        if ($response['paymentname'] != 'novalnet_cc' && $this->novalnet_cc3d_mode) { 
          if (!$this->checkHash ($response )) { 
            $smarty->assign('status', $parsed['status_text']);
            header('Location:bestellvorgang.php?wk=1');
            exit;
          }
        }
        if ($response['status'] == 100) {
            $this->changeOrderStatus( $order );
        }
        $comments       = $this->updateOrderComments( $response, $order, $this->payment_name );

        $this->addReferenceToComment( $order, $comments, 'FALSE' );
        $this->sendMail($order->kBestellung, MAILTEMPLATE_BESTELLUNG_AKTUALISIERT);
        $_SESSION['novalnet']['complete'] = 1;
        $this->postBackCall( $this->payment_name, $response, $order );
    }
    else {
      $_SESSION['novalnet']['error'] = utf8_decode($response['status_text']);
      $order = $_SESSION['novalnet']['order'];
      $_SESSION['novalnet_error'] = $response['status_text'];
    }

  }
  /**
   * make curl server call
   *
   * @data array
   * @url string
   * @return string
   */
  function novalnet_debit_call($data, $url){

    $ch = curl_init( $url );
    curl_setopt( $ch, CURLOPT_POST, 1 );
    curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );
    curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 0 );
    curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false );
    curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER,1 );
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
    curl_setopt( $ch, CURLOPT_TIMEOUT, 240 );
    if ($this->proxy) {
      curl_setopt($ch, CURLOPT_PROXY,  $this->proxy);
    }
    $response = curl_exec( $ch );
    curl_close( $ch );
    return $response;
  }

  /**
   * Format the telephone number
   *
   * @tel_no double
   * @return string
   */
  function getFormatedTelNumber($tel_no) {
    $sess_tel = trim($tel_no);
    if (!empty( $sess_tel )) {
       $aryTelDigits = str_split( $sess_tel, 4 );
       $count = 0;
       $str_sess_tel = '';
       foreach ($aryTelDigits as $ind=>$digits) {
        $count++;
        $str_sess_tel .= $digits;
        if($count==1) $str_sess_tel .= '-';
        else $str_sess_tel .= ' ';
       }
       $str_sess_tel=trim($str_sess_tel);
       if ($str_sess_tel)
          $sess_tel=$str_sess_tel;
    }
    return $sess_tel;
  }
  /* Validate mail & customer name */
  function validateServerParameters($order_update, &$data) {
    $data['first_name'] = trim($data['first_name']);
    $data['last_name']  = trim($data['last_name']);
    $amount = (in_array($this->payment_name, $this->password_payments)) ? $this->decode($data['amount']) : $data['amount'];

    if (empty($data['email']) || (empty($data['first_name']) && empty($data['last_name']))) {
        $_SESSION['novalnet']['error'] = NOVALNET_CUSTOMER_DETAILS_ERROR_MSG;
    }
    elseif ($data['email'] && !preg_match("/([\w\-]+\@[\w\-]+\.[\w\-]+)/",$data['email'])) {
        $_SESSION['novalnet']['error'] = NOVALNET_CUSTOMER_MAIL_ERROR_MSG;
    }
    elseif (!$this->isDigits($data['key'])) {
      $_SESSION['novalnet']['error'] = NOVALNET_KEY_ERROR_MSG;
    }
    elseif (!$this->isDigits($amount)) {
      $_SESSION['novalnet']['error'] = NOVALNET_AMOUNT_ERROR_MSG;
    }
    if (isset($_SESSION['novalnet']['error']) && !empty($_SESSION['novalnet']['error'])) {
      $this->returnOnError($order_update);
    }
	if(empty($data['first_name']) || empty($data['last_name'])) {
	   $name = $data['first_name'].$data['last_name'];
	   
	   list($data['first_name'],$data['last_name']) = preg_match('/\s/',$name) ? explode(' ',$name, 2) : array($name,$name); 
	}
  }
  
  function returnOnError($order_update) {
     if (($_SESSION['Zahlungsart']->nWaehrendBestellung == 0 && $_SESSION['Kunde']->nRegistriert == 0) || ($_SESSION['Zahlungsart']->nWaehrendBestellung == 0 && $order_update == true) ) {
        $this->unsetSesions();
        header( 'Location:' . URL_SHOP . '/novalnet_return.php' );
        exit;
      }
      if (!empty( $_SESSION['novalnet']['error'] ) && $_SESSION['Zahlungsart']->nWaehrendBestellung == 0) {
        header( 'Location:' . URL_SHOP . '/novalnet_return.php' );
        exit;
      }
      header( 'Location:' . gibShopURL() . '/bestellvorgang.php?editZahlungsart=1&' );
      exit;
  }
  /* To check element is digit */
  public function isDigits($element) {
    return(preg_match("/^[0-9]+$/", $element));
  }
   /**
   * Get the IP address of the end user
   *
   * @param  none
   * @return array
   */
  function getRealIpAddr() {
	return $_SERVER['REMOTE_ADDR'];
  }
}
?>
