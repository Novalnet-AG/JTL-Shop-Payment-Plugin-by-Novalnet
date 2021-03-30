<?php
#########################################################
#                                                       #
#  CC / CREDIT CARD payment method class                #
#  This module is used for real time processing of      #
#  Credit card data of customers.                       #
#                                                       #
#  Released under the GNU General Public License.       #
#  This free contribution made by request.              #
#  If you have found this script useful  a small        #
#  recommendation as well as a comment on merchant form #
#  would be greatly appreciated.                        #
#                                                       #
#  Script : novalnet_cc.class.php                       #
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
class novalnet_cc extends novalnetgateway {

  public $vendorid;
  public $productid;
  public $authcode;
  public $tariffid;
  public $testmode;
  public $manual_check_limit;
  public $productid_2;
  public $tariffid_2;
  public $autoFillFields;
  public $payment_name = 'novalnet_cc';

  function __construct() {
    global $smarty, $oPlugin, $hinweis;

    $this->name    = NOVALNET_CC_WAWI_NAME;
    $this->doAssignConfigVarsToMembers();
    $this->setError();
    if ($this->novalnet_cc3d_mode) { 

		if (isset($_REQUEST['status']) && $_REQUEST['status'] && $_REQUEST['paymentname'] == 'novalnet_cc' && isset($_SESSION['novalnet']['nWaehrendBestellung']) && $_SESSION['novalnet']['nWaehrendBestellung'] == 'Nein') {
			$this->orderComplete($_REQUEST);
		}
		if(isset($_REQUEST['status']) && $_REQUEST['status'] != 100 && !empty($_REQUEST['status_text']) && $_REQUEST['paymentname'] == 'novalnet_cc') {
			$hinweis = utf8_decode($_REQUEST['status_text']);
		}
	}
 }

  function preparePaymentProcess($order) { 
    global $Einstellungen, $DB, $smarty, $oPlugin;
    $Zahlung_vor_Bestell = '';
    $order_no = '';
    $order_update = $this->returnOrderType();
    if ($order_update == true || empty($_SESSION['post_array']['nn_type']) || !isset($_SESSION['post_array']['nn_cardno_id']) || empty($_SESSION['post_array']['nn_cardno_id']) || !isset($_SESSION['post_array']['nn_unique_id']) || empty($_SESSION['post_array']['nn_unique_id']) || empty($_SESSION['post_array']['nn_holdername']) || empty($_SESSION['post_array']['nn_cvvnumber']) || empty($_SESSION['post_array']['nn_expmonth']) || empty($_SESSION['post_array']['nn_expyear'])) {
       $_SESSION['novalnet']['error'] = NOVALNET_UPDATE_SUCESSORDER_ERRORMSG;
       if ($order_update == true || ($_SESSION['Kunde']->nRegistriert == '0' && $_SESSION["Zahlungsart"]->nWaehrendBestellung == 0)) {
        header('Location:' . gibShopURL() . '/novalnet_return.php');
        exit;
      }
      header( 'Location:' . gibShopURL() . '/bestellvorgang.php?wk=1' );
      exit;
    }
    if ($order_update == true) {
      $Zahlung_vor_Bestell = $this->checkOrderOnUpdate($order, 'novalnetkreditkarte');
    }
    $this->basicValidation( $order_update );

    if ($this->novalnet_cc3d_mode) {
		$params['uniqueid']     = uniqid();
		$params['cFailureURL']  = gibShopURL() . '/bestellvorgang.php?editZahlungsart=1&' . SID;
		$amount = $this->amountConversion( $order );
		$this->doCheckManualCheckLimit( $amount );
		$this->setReturnUrls( $params, $order_update, $Zahlung_vor_Bestell, $order);
		$params['order_no'] = ($_SESSION["Zahlungsart"]->nWaehrendBestellung == 0 && $order_update == false) ? $order->cBestellNr : '';
		$params['order_comments'] = ($_SESSION['Zahlungsart']->nWaehrendBestellung == 1 && $order_update == false ) ? 'TRUE' : 'FALSE';
		$_SESSION['novalnet']['order']        = $order;
		$this->bulidBasicParams( $data, $order, $params['uniqueid'] );
		$this->buildCommonParams( $data, $order, $_SESSION['Kunde'] );
		$this->additionalParams( $data, $params );
		$this->validateServerParameters($order_update, $data);
		if (!empty( $params['order_no'] )) {
			$data['order_no']    = $params['order_no'];
		}

		if(!empty($data)){
			$buildForm = $this->buildRedirectionForm($data);
			unset ($_SESSION['post_array']['nn_holdername']);
			unset ($_SESSION['post_array']['nn_cardnumber']);
			unset ($_SESSION['post_array']['nn_expmonth']);
			unset ($_SESSION['post_array']['nn_expyear']);
			unset ($_SESSION['post_array']['nn_cvvnumber']);

			if ($_SESSION['Kunde']->nRegistriert === 0 && $_SESSION["Zahlungsart"]->nWaehrendBestellung == 0) {
				unset($_SESSION['Kunde']);
				unset($_SESSION['Warenkorb']);
			}
			if ($_SESSION["Zahlungsart"]->nWaehrendBestellung == 0 && $order_update == false) {
				if ($_SESSION['Kunde']->nRegistriert != '0') {
					$GLOBALS["DB"]->executeQuery("delete wpp from twarenkorbperspos as wpp left join twarenkorbpers as wp on wpp.kWarenkorbPers= wp.kWarenkorbPers  where wp.kKunde='".$_SESSION['Kunde']->nRegistriert."'", 4);
				}
				unset($_SESSION['Warenkorb']);
				if (!empty($_SESSION['Kupon'])){
					unset($_SESSION['Kupon']);
				}
			}
			echo $buildForm;
			exit();
		}
	} else {
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

		unset ($_SESSION['post_array']['nn_type']);
		unset ($_SESSION['post_array']['nn_holdername']);
		unset ($_SESSION['post_array']['nn_expmonth']);
		unset ($_SESSION['post_array']['nn_expyear']);
		unset ($_SESSION['post_array']['nn_cvvnumber']);
	}
   if ($_SESSION["Zahlungsart"]->nWaehrendBestellung == 0 && $order_update == false) {
      if ($parsed['status'] == 100) {
        unset($_SESSION['post_array']);
        $this->changeOrderStatus($order);
        $comments = $this->updateOrderComments( $parsed, $order, $this->payment_name );
        $this->addReferenceToComment($order, $comments, 'FALSE');
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
    $cc_css 	= NOVALNET_CC_CSS_STYLE;
    $cc_css_val = NOVALNET_CC_CSS_STYLE_VAL;
    
    $smarty->assign('ccerror', false);
    $loading_logo = gibShopURL() . '/' . PFAD_PLUGIN . 'novalnetag/version/213/paymentmethod/logos/novalnet-loading-icon.gif';
    
    if ($GLOBALS['oSprache']->cISOSprache == 'ger') {
        $lang_payment_name = ($_SESSION['Zahlungsart']->angezeigterName['ger'] != '')?$_SESSION['Zahlungsart']->angezeigterName['ger']:NOVALNET_CC_ADDITIONAL_NAME;
    }
    else {
        $lang_payment_name = ($_SESSION['Zahlungsart']->angezeigterName['eng'] != '')?$_SESSION['Zahlungsart']->angezeigterName['eng']:NOVALNET_CC_ADDITIONAL_NAME;
    }
    $this->novalnet_session_unset();

    if (isset($aPost_arr['cc_owner']) || isset($aPost_arr['cc_type'])) {
        $aPost_arr['nn_type']           = isset($aPost_arr['cc_type']) ? trim($aPost_arr['cc_type']) : '';
        $aPost_arr['nn_holdername']     = isset($aPost_arr['cc_owner']) ? trim($aPost_arr['cc_owner']) : '';
        $aPost_arr['nn_cvvnumber']      = isset($aPost_arr['cc_cid']) ? trim($aPost_arr['cc_cid']): '';
        $aPost_arr['nn_expmonth']       = isset($aPost_arr['cc_exp_month']) ? $aPost_arr['cc_exp_month'] : '';
        $aPost_arr['nn_expyear']        = isset($aPost_arr['cc_exp_year']) ? $aPost_arr['cc_exp_year'] : '';
        $aPost_arr['nn_cardno_id']      = isset($aPost_arr['cc_pan_hash']) ? $aPost_arr['cc_pan_hash'] : '';
        $_SESSION['novalnet']['cc_pan_hash'] = (($this->autoFillFields) ? $aPost_arr['nn_cardno_id'] : '');
        $aPost_arr['nn_unique_id']      = isset($aPost_arr['cc_unique_id']) ? $aPost_arr['cc_unique_id'] : '';
        $_SESSION['novalnet']['cc_unique_id'] = (($this->autoFillFields) ? $aPost_arr['nn_unique_id'] : '');
        $_SESSION['novalnet']['cc_fldvalidator'] = $aPost_arr['cc_fldvalidator']      = isset($aPost_arr['cc_fldvalidator']) ? $aPost_arr['cc_fldvalidator'] : '';
    }
    if(isset($_SESSION['novalnet']['email']) && $_SESSION['novalnet']['email'] != $_SESSION['Kunde']->cMail)
        $_SESSION['novalnet']['cc_pan_hash'] ='';
		
    $nn_hash = (($this->autoFillFields && isset($_SESSION['novalnet']['cc_pan_hash'])) ? $_SESSION['novalnet']['cc_pan_hash'] : '');
    $fldVdr  = isset($_SESSION['novalnet']['cc_fldvalidator']) ? $_SESSION['novalnet']['cc_fldvalidator'] : '';
    $nn_uniq = isset($_SESSION['novalnet']['cc_unique_id']) ? $_SESSION['novalnet']['cc_unique_id'] : '';
    $cc_name = $_SESSION['Kunde']->cVorname.' '.$_SESSION['Kunde']->cNachname;
    $cc_cvc  = isset($_SESSION['post_array']['nn_cvvnumber']) ? $_SESSION['post_array']['nn_cvvnumber'] : '';
    $_SESSION['novalnet']['email'] = $_SESSION['Kunde']->cMail;
    $smarty->assign('lang_payment_name', $lang_payment_name);
    $smarty->assign('url_scheme', $this->http_url_scheme());
    $smarty->assign('loading_logo_path', $loading_logo);
    $smarty->assign('vendor_id', $this->vendorid);
    $smarty->assign('auth_code', $this->authcode);
    $smarty->assign('nn_hash', $nn_hash);
    $smarty->assign('fldVdr', $fldVdr);
    $smarty->assign('cc_name', $cc_name);
    $smarty->assign('cc_css', $cc_css);
    $smarty->assign('cc_css_val', $cc_css_val);
    $smarty->assign('payment_id', $this->setPaymentKey());
    $smarty->assign('lang_cc_description', NOVALNET_CC_PAYMENT_DESC);
    if ($this->testmode == '1')
      $smarty->assign('lang_cc_test_mode_info', NOVALNET_TESTMODE_MSG);
    if ( $GLOBALS['oSprache']->cISOSprache == 'ger' )
      $smarty->assign('lang_code', 'DE');
    else
      $smarty->assign('lang_code', 'EN');
    if (!$this->basicValidationOnhandleAdditional()) {
      $smarty->assign('ccerror', true);
      return false;
    }

    if (!empty($aPost_arr['nn_type']) || !empty($aPost_arr['nn_holdername']) || !empty($aPost_arr['nn_cvvnumber'] ) || !empty($aPost_arr['nn_expmonth'] ) || !empty($aPost_arr['nn_expyear'])) {
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
    if ($this->novalnet_cc3d_mode) {
        $response = $_REQUEST;
    }else {
        $response = $_SESSION['novalnet']['success'];
    }
    $amount = $this->amountConversion( $order );
    $this->doCheckManualCheckLimit( $amount );
    $this->changeOrderStatus($order);
    $this->postBackCall( $this->payment_name, $response, $order );
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
	  if ($this->novalnet_cc3d_mode) {
		$response = $_REQUEST;
	  }else {
		$response = $_SESSION['novalnet']['success'];
	  }
    return $this->verifyNotification_first($order, $hash, $args, $response);
  }
}
