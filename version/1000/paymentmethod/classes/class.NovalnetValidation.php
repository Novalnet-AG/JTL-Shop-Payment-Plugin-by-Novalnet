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
 * Script : class.NovalnetValidation.php
 *
 */

require_once('class.Novalnet.php');

class NovalnetValidation extends NovalnetGateway
{
	/**
	 *
	 * Constructor
	 *
	 */
	public function __construct()
	{
		$this->doAssignConfigVarsToMembers();
	}

	/**
	 * Error set on basic param validation
	 *
	 * @param bool $orderUpdate
	 * @param obj  $obj
	 * @return none
	 */
	public function basicValidation($orderUpdate, $obj)
	{
		$this->basicParamValidation($obj);

		$novalnetInterface = new NovalnetInterface();

		if (!empty($_SESSION['novalnet']['error'])) {
			$novalnetInterface->returnOnError($orderUpdate, $_SESSION['novalnet']['error']);
		}
	}

	/**
	 * Get the value for the connection type
	 *
	 * @param none
	 * @return string $http_url['scheme']
	 */
	public static function httpUrlScheme()
	{
		$http_url = parse_url(gibShopURL());
		return $http_url['scheme'];
	}

	/**
	 * Basic configuration validation
	 *
	 * @param object $obj
	 * @return bool
	 */
	public function isConfigInvalid($obj)
	{
		if ((isset($obj->vendorid) && !self::isDigits($obj->vendorid)) || (isset($obj->productid) && !self::isDigits($obj->productid)) || (isset($obj->authcode) && empty($obj->authcode)) || (isset($obj->tariffid) && !self::isDigits($obj->tariffid))) {
			return true;
		}
	}

	/**
	 * Validation for basic parameter values
	 *
	 * @param object $obj
	 * @return none
	 */
	public function basicParamValidation($obj)
	{
		global $oPlugin;

		if ($this->isConfigInvalid($obj) || ((in_array($obj->paymentName, $obj->redirectPayments) || ($obj->paymentName == 'novalnet_cc' && $obj->cc3d_active_mode)) && empty($obj->key_password)) || self::isSubscriptionInvalid($obj)) {
			$_SESSION['novalnet']['error'] = utf8_encode(html_entity_decode($oPlugin->oPluginSprachvariableAssoc_arr['__NN_merchant_error']));
		}

		if ((in_array($obj->paymentName,$obj->invoicePayments))) {
			if (!$obj->payment_reference1 && !$obj->payment_reference2 && !$obj->payment_reference3) {
				$_SESSION['novalnet']['error'] = utf8_encode(html_entity_decode($oPlugin->oPluginSprachvariableAssoc_arr['__NN_invoice_payments_reference_error']));
			}
		}
	}

	/**
	 * Subscription parameter validation
	 *
	 * @param object $obj
	 * @return bool
	 */
	public function isSubscriptionInvalid($obj)
	{
		if ((!empty($obj->tariff_period2) && !self::isDigits($obj->tariff_period2_amount)) || (self::isDigits($obj->tariff_period2_amount) && empty($obj->tariff_period2))){
			return true;
		}
	}

	/**
	 * Validation for basic params on form payments
	 *
	 * @param string $obj
	 * @return bool
	 */
	public function basicValidationOnhandleAdditional($obj)
	{
		global $oPlugin;

		$this->basicParamValidation($obj);

		if ($obj->paymentName == 'novalnet_sepa') {
			if ((!empty($obj->sepa_due_date) && (!self::isDigits($obj->sepa_due_date) || $obj->sepa_due_date < 7)) || ( $obj->sepa_due_date == '0' && strlen($obj->sepa_due_date) > 0 )) {
				$_SESSION['novalnet']['error'] = utf8_encode(html_entity_decode($oPlugin->oPluginSprachvariableAssoc_arr['__NN_sepa_duedate_error']));
			}
		} elseif ($obj->manual_check_limit && !self::isDigits($obj->manual_check_limit)) {
				$_SESSION['novalnet']['error'] = utf8_encode(html_entity_decode($oPlugin->oPluginSprachvariableAssoc_arr['__NN_merchant_error']));
		} 
		
		if (!empty($_SESSION['novalnet']['error'])) {
			$this->assignError($_SESSION['novalnet']['error']);
		}
		return true;
	}

	/**
	 * Error assigning to template for display
	 *
	 * @param string $error
	 * @return bool
	 */
	public function assignError($error)
	{
		global $smarty;

		$smarty->assign(array('error' => true, 'error_desc' => $error));
		return false;
	}

	/**
	 * To find string is present in another string
	 *
	 * @param string $string
	 * @param string $substring
	 * @return bool
	 */
	public static function validateString($string, $substring)
	{
		return (strpos( $string, $substring ) === false ? false : true);
	}

	/**
	 * To check customer mail and name
	 *
	 * @param bool $orderUpdate
	 * @param array $data
	 * @return none
	 */
	public function validateCustomerParameters($orderUpdate, $data)
	{
		global $oPlugin;

		$novalnetInterface = new NovalnetInterface();

		if (empty($data['email']) || (empty($data['first_name']) && empty($data['last_name'])) || !valid_email($data['email'])) {
			$_SESSION['novalnet']['error'] = utf8_encode(html_entity_decode($oPlugin->oPluginSprachvariableAssoc_arr['__NN_customer_details_error']));
			$novalnetInterface->returnOnError($orderUpdate, $_SESSION['novalnet']['error']);
		}
	}

	/**
	 * To check whether fraud prevention can be enabled or not
	 *
	 * @param object $obj
	 * @return bool
	 */
	public function isValidFraudCheck($obj)
	{
		return !(($obj->paymentName == 'novalnet_cc' && $obj->cc3d_active_mode) || (isset($obj->pin_amount) && $obj->pin_amount > 0 && (($_SESSION['Warenkorb']->gibGesamtsummeWarenExt(array(C_WARENKORBPOS_TYP_ARTIKEL), true) * 100 ) < $obj->pin_amount)) || (!in_array($_SESSION['Kunde']->cLand, array('DE','AT','CH'))) || $_SESSION['Zahlungsart']->nWaehrendBestellung == 0);
	}

	/**
	 * To check whether an element is digit
	 *
	 * @param string $element
	 * @return bool
	 */
	public static function isDigits($element)
	{
		return (preg_match('/^[0-9]+$/', $element));
	}
}
