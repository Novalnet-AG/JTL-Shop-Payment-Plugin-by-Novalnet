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
 * Script : novalnet_cc.class.php
 *
 */

require_once( PFAD_ROOT . PFAD_INCLUDES_MODULES . 'PaymentMethod.class.php' );
require_once( 'class.Novalnet.php' );

class novalnet_cc extends NovalnetInterface
{
	public $paymentName = 'novalnet_cc';

	/**
	 *
	 * Constructor
	 *
	 */
	function __construct()
	{
		$this->doAssignConfigVarsToMembers();
		$this->setError();
		if (isset($_SESSION['novalnet']['nWaehrendBestellung']) && $_SESSION['novalnet']['nWaehrendBestellung'] == 'Nein') {
			if ((isset($_REQUEST['status']) && $_REQUEST['status'] == 100 && $_REQUEST['inputval3'] == $this->paymentName)){
				$this->orderComplete();
			}
		}

		if ($_REQUEST['status'] != 100 && !empty($_REQUEST['status_text']))
			$_SESSION['novalnet']['error'] = utf8_decode($_REQUEST['status_text']);
	}

	/**
	 * Initialise the Payment process
	 *
	 * @param object $order
	 * @return none
	 */
	function preparePaymentProcess($order)
	{
		global $oPlugin;

		$orderUpdate = $this->returnOrderType();

		if ($_SESSION['novalnet']['fraud_module_active'])
			$this->orderAmountCheck($order->fGesamtsumme);
			
		$novalnetValidation = new NovalnetValidation();

		if ($orderUpdate || (empty($_SESSION[$this->paymentName]['tid']) && ((isset($_SESSION[$this->paymentName]['cc_pan_hash']) && empty($_SESSION[$this->paymentName]['cc_pan_hash'])) || (isset($_SESSION[$this->paymentName]['nn_unique_id']) && empty($_SESSION[$this->paymentName]['nn_unique_id'])) || !isset($_SESSION[$this->paymentName]['cc_pan_hash'])))) {
			$_SESSION['nn_order'] = $order->cBestellNr;
			$_SESSION['novalnet']['error'] = $oPlugin->oPluginSprachvariableAssoc_arr['__NN_order_not_sucessful'];

			if ($orderUpdate) {
				header('Location:' . gibShopURL() . '/novalnet_return.php');
				exit;
			}
			header('Location:' . gibShopURL() . '/bestellvorgang.php?editZahlungsart=1');
			exit;
		}
		
		if (!$novalnetValidation->basicValidationOnhandleAdditional($this))
			return false;

		if (empty($_SESSION[$this->paymentName]['tid'])) {
			$this->doPaymentCall($order);
		} else {
			$this->doSecondCall($order, $orderUpdate);
		}
	}

	/**
	 * To check whether the payment method can be displayed in the payment page
	 *
	 * @param array $args_arr
	 * @return bool
	 */
	function isValidIntern($args_arr = array())
    {
		return !($this->isPaymentEnabled($this->paymentName));
    }

	/**
	 * To display the payment form
	 *
	 * @param object $aPost_arr
	 * @return bool
	 */
	function handleAdditional($aPost_arr)
	{
		global $smarty;

		if (!empty($_SESSION['novalnet']['mail']) && ($_SESSION['novalnet']['mail'] != $_SESSION['Kunde']->cMail)) {
			unset($_SESSION[$this->paymentName]['cc_pan_hash']);
		}

		$_SESSION['novalnet']['mail'] = $_SESSION['Kunde']->cMail;

		$oPlugin = NovalnetGateway::getPluginObject();

		$novalnetValidation = new NovalnetValidation();

		$placeholder = array('__NN_credit_card_type','__NN_select_type','__NN_credit_card_name','__NN_credit_card_number','__NN_credit_card_date','__NN_credit_card_month','__NN_credit_card_year','__NN_credit_card_cvc','__NN_merchant_error','__NN_credit_card_desc','__NN_merchant_error','__NN_credit_card_error','__NN_javascript_error','__NN_callback_phone_number','__NN_callback_sms','__NN_callback_mail','__NN_callback_pin','__NN_callback_forgot_pin','__NN_callback_telephone_error','__NN_callback_mobile_error','__NN_callback_email_pin','__NN_callback_pin_error','__NN_callback_pin_error_empty','__NN_testmode');

		$formFields = NovalnetGateway::getLanguageText($placeholder);

		if ( $this->displayFraudCheck() && isset($_SESSION[$this->paymentName]['tid'])) {
			return true;
		}

		list( $this->vendorid, $this->authcode ) = NovalnetGateway::getAffiliateDetails();

		$smarty->assign( array(
					'payment_name'					  => NovalnetGateway::getPaymentName($aPost_arr['Zahlungsart']),
					'vendor_id'        				  => $this->vendorid,
					'auth_code'         			  => $this->authcode,
					'nn_hash' 						  => (($this->autorefill) ? $_SESSION[$this->paymentName]['cc_pan_hash'] : ''),
		            'uniq_value' 					  => $this->getRandomString(30),
		            'cc_name'           			  => $_SESSION['Kunde']->cVorname.' '.$_SESSION['Kunde']->cNachname,
		            'cc_year_limit'    				  => $this->getValidYearLimit(),
		            'cc_amex_accept'                  => $this->cc_amex_accept,
					'filePath'          			  => gibShopURL() . '/' . PFAD_PLUGIN . $oPlugin->cVerzeichnis . '/' . PFAD_PLUGIN_VERSION . $oPlugin->nVersion . '/' . PFAD_PLUGIN_PAYMENTMETHOD,
					'lang_cc_customer_email'		  => $_SESSION['Kunde']->cMail,
			        'lang_cc_test_mode_info'		  => (($this->testmode) ? $formFields['testmode'] : ''),
			        'nn_lang'                         => $formFields));

		if (!$novalnetValidation->basicValidationOnhandleAdditional($this)) {
			return false;
		} elseif (isset($aPost_arr['payment'])) {
			$formArray = array_map('trim', $aPost_arr);
			if (empty($_SESSION[$this->paymentName]['tid']))
				$_SESSION[$this->paymentName] = $formArray;
			else
				$_SESSION['post_array'] = $formArray;
			return true;
		}
	}

	/**
	 * Process when notification url is handled
	 *
	 * @param object $order
	 * @param string $hash
	 * @param array $args
	 * @return none
	 */
	function handleNotification($order, $hash, $args)
	{
		$response = $this->cc3d_active_mode ? $_REQUEST : $_SESSION['novalnet']['success'];
		$this->handleViaNotification($order, $response);
	}

	/**
	 * When order is finalized
	 *
	 * @param object $order
	 * @param string $hash
	 * @param array $args
	 * @return bool
	 */
	function finalizeOrder($order, $hash, $args)
	{
		$response = $this->cc3d_active_mode ? $_REQUEST : $_SESSION['novalnet']['success'];
		return parent::verifyNotification($order, $hash, $args, $response);
	}
}
