<?php
#########################################################
#                                                       #
#  ELVAT / DIRECT DEBIT payment method class            #
#  This module is used for real time processing of      #
#  Austrian Bankdata of customers.                      #
#                                                       #
#  Released under the GNU General Public License.       #
#  This free contribution made by request.              #
#  If you have found this script usefull a small        #
#  recommendation as well as a comment on merchant form #
#  would be greatly appreciated.                        #
#                                                       #
#  Script : novalnet_elv_at.class.php                   #
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

class novalnet_elv_at extends novalnetgateway {

  public $vendorid;
  public $productid;
  public $authcode;
  public $tariffid;
  public $testmode;
  public $manual_check_limit;
  public $productid_2;
  public $tariffid_2;
  public $payment_name = 'novalnet_elvat';


  function init() {
    global $oPlugin, $hinweis;

    $this->name = NOVALNET_ELVAT_WAWI_NAME;
    $this->doAssignConfigVarsToMembers();
    $this->setError();
  }

  function preparePaymentProcess($order) {
    global $Einstellungen, $DB, $smarty, $oPlugin;
    $order_no = '';
    $order_update = $this->returnOrderType();

   if ($order_update == true || !preg_match('/[0-9]+$/',$_SESSION['post_array']['nn_bankcode']) || !preg_match('/[0-9]+$/',$_SESSION['post_array']['nn_accountnumber']) || empty($_SESSION['post_array']['nn_holdername'])) {
        $_SESSION['novalnet']['error'] = NOVALNET_UPDATE_SUCESSORDER_ERRORMSG;
      if ($order_update == true || ($_SESSION['Kunde']->nRegistriert == '0' && $_SESSION["Zahlungsart"]->nWaehrendBestellung == 0)) {
        header('Location:' . URL_SHOP . '/novalnet_return.php');
        exit;
      }
      header( 'Location:' . gibShopURL() . '/bestellvorgang.php?wk=1' );
      exit;
    }
    $this->basicValidation( $order_update );
    $amount = $this->amountConversion( $order );
    $this->doCheckManualCheckLimit( $amount );
    $params['order_no'] = ($_SESSION["Zahlungsart"]->nWaehrendBestellung == 0 && $order_update == false) ? $order->cBestellNr : '';
    $params['order_comments'] = ($_SESSION['Zahlungsart']->nWaehrendBestellung == 1 && $order_update == false ) ? 'TRUE' : 'FALSE';
    $this->bulidBasicParams( $data, $order);
    $this->buildCommonParams( $data, $order, $_SESSION['Kunde'] );
    $this->additionalParams( $data, $params );
    $this->validateServerParameters($order_update, $data);
    if (!empty($params['order_no'])) {
      $data['order_no']    = $params['order_no'];
    }

    $query    = http_build_query($data, '', '&');
    $url      = $this->setPaymentUrl();
    $response = $this->novalnet_debit_call( $query, $url );
    parse_str( $response, $parsed );

    unset ($_SESSION['post_array']['nn_bankcode']);
    unset ($_SESSION['post_array']['nn_accountnumber']);
    unset ($_SESSION['post_array']['nn_holdername']);

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


    $smarty->assign('lang_elv_at_test_mode_info', '');
    
	if ( $GLOBALS['oSprache']->cISOSprache == 'ger' ){
		$lang_payment_name = ($_SESSION['Zahlungsart']->angezeigterName['ger'] != '') ? $_SESSION['Zahlungsart']->angezeigterName['ger'] : NOVALNET_ELVAT_ADDITIONAL_NAME;
	}else {
		$lang_payment_name = ($_SESSION['Zahlungsart']->angezeigterName['eng'] != '') ? $_SESSION['Zahlungsart']->angezeigterName['eng'] : NOVALNET_ELVAT_ADDITIONAL_NAME;
	}
	unset($_SESSION['novalnet']['nnde_holdername'],$_SESSION['novalnet']['nnde_accountnumber'], $_SESSION['novalnet']['nnde_bankcode'], $_SESSION['novalnet']['nnde_acdc'], $_SESSION['novalnet']['sepa_field_validator'], $_SESSION['novalnet']['nn_sepapanhash'], $_SESSION['novalnet']['sepa_mandate_ref'], $_SESSION['novalnet']['sepa_mandate_date'], $_SESSION['novalnet']['cc_pan_hash'], $_SESSION['novalnet']['cc_unique_id'], $_SESSION['novalnet']['cc_fldvalidator']);
	
	if (isset($aPost_arr['nn_holdername']) && !empty($aPost_arr['nn_holdername']) ) {
		$aPost_arr['nn_holdername']	= isset($aPost_arr['nn_holdername']) ? trim($aPost_arr['nn_holdername']) : '';
		$_SESSION['novalnet']['nnat_holdername'] = (($this->autoFillFields) ? $aPost_arr['nn_holdername'] : ''); 
		$aPost_arr['nn_accountnumber'] = isset($aPost_arr['nn_accountnumber']) ? str_replace(' ', '', trim($aPost_arr['nn_accountnumber'])) : '';
		$_SESSION['novalnet']['nnat_accountnumber'] = (($this->autoFillFields) ? $aPost_arr['nn_accountnumber'] : '');
		$aPost_arr['nn_bankcode']	= isset($aPost_arr['nn_bankcode']) ? str_replace(' ', '', trim($aPost_arr['nn_bankcode'])) : '';
		$_SESSION['novalnet']['nnat_bankcode'] = (($this->autoFillFields) ? $aPost_arr['nn_bankcode'] : '');
	}
 
	$at_acc_name  = (isset($_SESSION['novalnet']['nnat_holdername']) ? $_SESSION['novalnet']['nnat_holdername'] : '');
	$at_acc_no 	  = (isset($_SESSION['novalnet']['nnat_accountnumber']) ? $_SESSION['novalnet']['nnat_accountnumber'] : '');
	$at_bank_code = (isset($_SESSION['novalnet']['nnat_bankcode']) ? $_SESSION['novalnet']['nnat_bankcode'] : '');
	
    $smarty->assign('lang_payment_name', $lang_payment_name);
    $smarty->assign('at_acc_name', $at_acc_name);
    $smarty->assign('at_acc_no', $at_acc_no);
    $smarty->assign('at_bank_code', $at_bank_code);
    $smarty->assign('lang_field_validation_message', NOVALNET_ACCOUNT_INFO_MSG);
    $smarty->assign('lang_account_holder', NOVALNET_ELVATDE_ACCOUNT_HOLDER);
    $smarty->assign('lang_account_number', NOVALNET_ELVATDE_ACCOUNT_NUMBER);
    $smarty->assign('lang_bank_code', NOVALNET_ELVATDE_ACCOUNT_BANKCODE);
    $smarty->assign('lang_at_description', NOVALNET_ELVATDE_PAYMENT_DESCRIPTION);
    if ($this->testmode == '1') {
        $smarty->assign('lang_elv_at_test_mode_info', NOVALNET_TESTMODE_MSG);
      }

    if (!empty($aPost_arr['nn_holdername']) || !empty($aPost_arr['nn_accountnumber']) || !empty($aPost_arr['nn_bankcode'])) {
      
     if ($this->basicValidationOnhandleAdditional()) {
        if( $this->validateAccountDetails( $aPost_arr )) {
                $_SESSION['post_array'] = $aPost_arr;      
              return true;
        }else {
          return false;        
        }
      }
      else {
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

  function handleNotification($order, $paymentHash, $args){
    global $oPlugin;

    $amount = $this->amountConversion( $order );
    $this->doCheckManualCheckLimit( $amount );
    $this->changeOrderStatus($order);
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
