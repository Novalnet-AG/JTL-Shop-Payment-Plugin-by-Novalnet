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
 * Script : novalnet_sepa.class.php
 *
 */

require_once( PFAD_ROOT . PFAD_INCLUDES_MODULES . 'PaymentMethod.class.php' );
require_once( 'class.Novalnet.php' );

class novalnet_sepa extends NovalnetInterface
{
	public $paymentName = 'novalnet_sepa';

	/**
	 *
	 * Constructor
	 *
	 */
	public function __construct()
	{
		$this->doAssignConfigVarsToMembers();
		$this->setError();
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

		if ($_SESSION['novalnet']['fraud_module_active'])
			$this->orderAmountCheck($order->fGesamtsumme);

		$novalnetValidation = new NovalnetValidation();

		$orderUpdate = $this->returnOrderType();

		if ($orderUpdate || (empty($_SESSION[$this->paymentName]['tid']) && ((isset($_SESSION[$this->paymentName]['nn_sepaunique_id']) && empty($_SESSION[$this->paymentName]['nn_sepaunique_id'])) || (isset($_SESSION[$this->paymentName]['nn_sepapanhash']) && empty($_SESSION[$this->paymentName]['nn_sepapanhash'])) || !isset($_SESSION[$this->paymentName]['nn_sepapanhash'])))) {
			$_SESSION['nn_order'] = $order->cBestellNr;
			$_SESSION['novalnet']['error'] = $oPlugin->oPluginSprachvariableAssoc_arr['__NN_order_not_sucessful'];

			if ($orderUpdate) {
				header('Location:' . gibShopURL() . '/novalnet_return.php');
				exit;
			}
			header('Location:' . gibShopURL() . '/bestellvorgang.php?editZahlungsart=1');
			exit;
		}

		if (!$novalnetValidation->basicValidationOnhandleAdditional($this)) {
			return false;
		}

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
			unset($_SESSION[$this->paymentName]['nn_sepapanhash']);
		}

		$_SESSION['novalnet']['mail'] = $_SESSION['Kunde']->cMail;

		$oPlugin = NovalnetGateway::getPluginObject();

		$novalnetValidation = new NovalnetValidation();

		$placeholder = array('__NN_sepa_holder_name','__NN_sepa_country_name','__NN_sepa_account_number','__NN_sepa_bank_code','__NN_merchant_error','__NN_javascript_error','__NN_sepa_mandate_error','__NN_sepa_error','__NN_sepa_mandate_title','__NN_sepa_payee','__NN_sepa_payee_number','__NN_sepa_mandate_reference','__NN_sepa_mandate_text','__NN_sepa_mandate_paragraph','__NN_sepa_mandate_name','__NN_sepa_mandate_company','__NN_sepa_mandate_address','__NN_sepa_mandate_pincode','__NN_sepa_mandate_country','__NN_sepa_mandate_confirm_btn','__NN_sepa_mandate_close_btn','__NN_sepa_description','__NN_callback_phone_number','__NN_callback_sms','__NN_callback_mail','__NN_callback_pin','__NN_callback_forgot_pin','__NN_callback_telephone_error','__NN_callback_mobile_error','__NN_callback_email_pin','__NN_callback_pin_error','__NN_callback_pin_error_empty','__NN_testmode');

		$formFields = NovalnetGateway::getLanguageText($placeholder);

		if ( $this->displayFraudCheck() && isset($_SESSION[$this->paymentName]['tid'])) {
			return true;
		}

		list( $this->vendorid, $this->authcode ) = NovalnetGateway::getAffiliateDetails();

			$smarty->assign(array(
					'payment_name'				=> NovalnetGateway::getPaymentName($aPost_arr['Zahlungsart']),
					'filePath'          		=> gibShopURL() . '/' . PFAD_PLUGIN . $oPlugin->cVerzeichnis . '/' . PFAD_PLUGIN_VERSION . $oPlugin->nVersion . '/' . PFAD_PLUGIN_PAYMENTMETHOD,
					'country_list'				=> gibBelieferbareLaender($_SESSION['Kunde']->kKundengruppe),
					'lang_code'					=> NovalnetGateway::getShopLanguage(),
					'vendor_id'					=> $this->vendorid,
					'auth_code'					=> $this->authcode,
					'uniq_sepa_value'			=> $this->getRandomString(30),
					'nn_lang'					=> $formFields,
					'lang_sepa_test_mode_info'	=> ($this->testmode == '1') ? $formFields['testmode'] : '',
					'sepa_holder'				=> $_SESSION['Kunde']->cVorname.' '.$_SESSION['Kunde']->cNachname,
					'sepa_holder_company'		=> $_SESSION['Kunde']->cFirma,
					'sepa_holder_address'		=> $_SESSION['Kunde']->cHausnummer .','.$_SESSION['Kunde']->cStrasse,
					'sepa_holder_zip'			=> $_SESSION['Kunde']->cPLZ,
					'sepa_holder_city'			=> $_SESSION['Kunde']->cOrt,
					'sepa_holder_country'		=> $_SESSION['Kunde']->angezeigtesLand,
					'sepa_holder_mail'			=> $_SESSION['Kunde']->cMail,
					'panhash'					=> $this->getSepaRefillHash()));

		if (!$novalnetValidation->basicValidationOnhandleAdditional($this)) {
			return false;
		} elseif (isset($aPost_arr['payment'])) {
			$formArray = array_map('trim', $aPost_arr);
			if (empty($_SESSION[$this->paymentName]['tid'])) {
				$_SESSION[$this->paymentName] = $formArray;
			} else {
				$_SESSION['post_array'] = $formArray;
			}
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
		$this->handleViaNotification($order, $_SESSION['novalnet']['success']);
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
		return parent::verifyNotification($order, $hash, $args, $_SESSION['novalnet']['success']);
	}
}
