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
 * Script : class.NovalnetInterface.php
 *
 */

require_once('class.Novalnet.php');

class NovalnetInterface extends NovalnetGateway
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
	 * Forming payment parameters to paygate
	 * @param bool  $orderUpdate
	 * @param array $order
	 * @param array $paymentOnComplete
	 * @return array
	 */
	public function buildParametersToPaygate($orderUpdate, $order, $paymentOnComplete)
	{

		$novalnetValidation = new NovalnetValidation();
		$novalnetValidation->basicValidation($orderUpdate, $this);

		$params['uniqueid'] = '';

		if (in_array($this->paymentName,$this->redirectPayments) || ($this->paymentName == 'novalnet_cc' && $this->cc3d_active_mode)) {
			$params['uniqueid']    = uniqid();
			$params['cFailureURL'] = gibShopURL() . '/bestellvorgang.php?editZahlungsart=1&' . SID;
			$this->setReturnUrls($params, $orderUpdate, $paymentOnComplete, $order);
			$_SESSION['novalnet']['order'] = $order;
		}

		$data = array();
		$this->buildBasicParams($data, $order->fGesamtsumme, $params['uniqueid']);
		$this->buildCommonParams($data, $order->Waehrung->cISO, $_SESSION['Kunde']);
		$this->buildAdditionalParams($data, $params);
		$novalnetValidation->validateCustomerParameters($orderUpdate, $data);
		return $data;
	}

    /**
	 * After payment process with order complete option enabled
	 *
	 * @param array $order
	 * @return none
	 */
	public function afterPaymentProcessOnComplete($order)
	{
		$paymentHash = $this->generateHash($order);
		$cReturnURL  = $this->getNotificationURL($paymentHash) . '&sh=' . $paymentHash;
		header('Location:' . $cReturnURL);
		exit;
	}

    /**
	 * After payment process with order complete option disabled
	 *
	 * @param array $order
	 * @param array $parsed
	 * @return none
	 */
	public function afterPaymentProcess($order, $parsed)
	{
		$this->changeOrderStatus($order, ($parsed['status'] == 90 ? true : false));
		$comments = $this->updateOrderComments($parsed, $order);
		NovalnetGateway::addReferenceToComment($order->kBestellung, $comments);
		$this->postBackCall($parsed, $order->cBestellNr, $comments);
		$this->insertOrderIntoDB($parsed, $order->kBestellung);
		$this->sendMail($order->kBestellung, MAILTEMPLATE_BESTELLUNG_AKTUALISIERT);
		unset($_SESSION[$this->paymentName]);
		unset($_SESSION['nn_aff_id']);
	}

    /**
	 * Return during error
	 *
	 * @param bool $orderUpdate
	 * @param array $parsed
	 * @return none
	 */
	public function returnOnError($orderUpdate, $parsed)
	{
		if (isset($parsed)) {
			$_SESSION['novalnet']['error'] = utf8_decode($parsed);
		}

		if (($_SESSION['Zahlungsart']->nWaehrendBestellung == 0 && $orderUpdate ) || ($_SESSION['Zahlungsart']->nWaehrendBestellung == 0 && $_SESSION['Kunde']->nRegistriert == '0')) {
			$this->unsetSessions();
			header('Location:' . gibShopURL() . '/novalnet_return.php');
			exit;
		}
		header('Location:' . gibShopURL() . '/bestellvorgang.php?editZahlungsart=1');
		exit;
	}

    /**
	 * Process while handling handle_notification url
	 *
	 * @param array $order
	 * @param array $response
	 * @return none
	 */
	public function handleViaNotification($order, $response)
	{
		$this->changeOrderStatus($order, ($response['status'] == 90 ? true : false));
		$this->postBackCall($response, $order->cBestellNr, $order->cKommentar);
		$this->insertOrderIntoDB($response, $order->kBestellung);
		$paymenthash = $this->generateHash($order);
		unset($_SESSION[$this->paymentName]);
		unset($_SESSION['nn_aff_id']);
		header('Location: ' . gibShopURL() . '/bestellabschluss.php?i=' . $paymenthash);
		exit();
	}

    /**
	 * Performs redirection for payments
	 *
	 * @param array $order
	 * @param string $paymentType
	 * @return none
	 */
	public function doRedirectionCall($order, $paymentType)
	{
		global $oPlugin;

		$paymentOnComplete = '';
		$this->novalnetSessionUnset();
		$orderUpdate = $this->returnOrderType();

		if ($orderUpdate)
			$paymentOnComplete = $this->checkOrderOnUpdate($order->cBestellNr, $paymentType);

		$data = $this->buildParametersToPaygate($orderUpdate, $order, $paymentOnComplete);
		if (!empty($data)) {
			$this->unsetSessions();
			$formData    = '<form name="frmnovalnet" method="post" action="' . $this->setPaymentConfiguration() . '">';
			$formMessage = $oPlugin->oPluginSprachvariableAssoc_arr['__NN_redirection_text'];

			$formSubmit  = '<script>document.forms.frmnovalnet.submit();</script>';
			foreach ( $data as $k => $v ) {
				$formData .= '<input type="hidden" name="' . $k . '" value="' . $v . '" />' . "\n";
			}
			echo $formData . $formMessage . $formSubmit;
			exit();
		}
	}

    /**
	 * Make payment first call to server
	 *
	 * @param array $order
	 * @return none
	 */
	public function doPaymentCall($order)
	{
		global $oPlugin;

		$orderUpdate = $this->returnOrderType();

		$novalnetValidation = new NovalnetValidation();

		if ($this->paymentName == 'novalnet_cc' && $this->cc3d_active_mode) {
			$this->doRedirectionCall($order, 'novalnet_cc');
		} else {
			$data 	  = $this->buildParametersToPaygate( $orderUpdate, $order );
			$query    = http_build_query($data, '', '&');
			$response = $this->novalnetTransactionCall($query, $this->setPaymentConfiguration());
			parse_str( $response, $parsed );
		}

		if ($parsed['status'] == 100)  {
			$_SESSION['novalnet']['success'] = $parsed;
			if (isset($this->pin_by_callback) && $this->pin_by_callback != '0' && $novalnetValidation->isValidFraudCheck($this)) {
				$this->fraudModuleComments($parsed);
				$_SESSION['novalnet']['error'] = ($this->pin_by_callback == '3') ? $oPlugin->oPluginSprachvariableAssoc_arr['__NN_reply_by_mail_message'] : ($this->pin_by_callback == '2' ? $oPlugin->oPluginSprachvariableAssoc_arr['__NN_sms_pin_message']:$oPlugin->oPluginSprachvariableAssoc_arr['__NN_callback_pin_message']);
				$_SESSION['novalnet']['fraud_module_active'] = TRUE;
				header('Location:' . gibShopURL() . '/bestellvorgang.php?editZahlungsart=1');
				exit;
			}
			if ($_SESSION['Zahlungsart']->nWaehrendBestellung == 0) {
					$this->afterPaymentProcess($order, $parsed);
			} else {
					$this->afterPaymentProcessOnComplete($order);
			}
		} else {
			$_SESSION['novalnet']['tid'] = $parsed['tid'];
			$this->returnOnError($orderUpdate, $parsed['status_desc']);
		}
	}

    /**
	 * Make second call to server
	 *
	 * @param array $order
	 * @param bool $orderUpdate
	 * @return none
	 */
	public function doSecondCall($order, $orderUpdate)
	{
		global $oPlugin;

		if (!NovalnetValidation::isDigits($this->vendorid) || empty($this->authcode)) {
			$_SESSION['novalnet']['error'] = $oPlugin->oPluginSprachvariableAssoc_arr['__NN_second_call_error'];
			unset($_SESSION[$this->paymentName]['tid']);
			header('Location:'.gibShopURL() .'/bestellvorgang.php?editZahlungsart=1&');
			exit;
		}

		$requestType = ($_SESSION['post_array']['nn_forgot_pin']) ? 'TRANSMIT_PIN_AGAIN' : ($this->pin_by_callback == '3' ? 'REPLY_EMAIL_STATUS' : 'PIN_STATUS');

		$aryResponse = $this->transactionStatus($_SESSION[$this->paymentName]['tid'], $order->cBestellNr,$requestType);

		if ($aryResponse['status'] == 100 ) {
			$this->afterPaymentProcessOnComplete($order);
		} else {
			if ($aryResponse['status'] == '0529006') {
				$_SESSION[$this->paymentName.'_invalid']    = TRUE;
				$_SESSION[$this->paymentName.'_time_limit'] = time()+(30*60);
				$_SESSION['novalnet']['mail'] = $_SESSION['Kunde']->cMail;
				unset($_SESSION[$this->paymentName]['tid']);
			}elseif ($aryResponse['status'] == '0529008'){
				unset($_SESSION[$this->paymentName]['tid']);
			}
			$this->returnOnError($orderUpdate, $aryResponse['status_additional']);
		}
	}

    /**
	 * To check whether payment is enabled
	 *
	 * @param string $paymentType
	 * @return bool
	 */
	public function isPaymentEnabled($paymentType)
	{
		if (!empty($_SESSION['novalnet']['mail']) && ($_SESSION['novalnet']['mail'] != $_SESSION['Kunde']->cMail)) {
			unset($_SESSION[$paymentType.'_time_limit']);
		}

		$oPlugin = NovalnetGateway::getPluginObject();

		$novalnetValidation = new NovalnetValidation();

		$config = array('vendorid', 'productid', 'authcode', 'tariffid', 'key_password');
		foreach ($config as $configuration) {
			$this->$configuration = trim($oPlugin->oPluginEinstellungAssoc_arr[$configuration]);
		}
		$enabledStatus = $oPlugin->oPluginEinstellungAssoc_arr[$paymentType.'_enablemode'];
		if ((isset($_SESSION[$paymentType.'_invalid']) && (time() < $_SESSION[$paymentType.'_time_limit'])) || ($enabledStatus[$paymentType.'_enablemode'] == 0) || ($novalnetValidation->isConfigInvalid($this)) || (in_array($paymentType,$this->redirectPayments ) && empty($this->key_password))) {
			return true;
		}
	}

    /**
	 * To display additional fields for fraud prevention setup
	 *
	 * @param none
	 * @return none
	 */
	public function displayFraudCheck()
	{
		global $smarty;

		$this->novalnetSessionUnset();

		$novalnetValidation = new NovalnetValidation();

		if ($this->pin_by_callback == '0' || !$novalnetValidation->isValidFraudCheck($this)) {
			return true;
		} else {
			if (isset($_SESSION[$this->paymentName]['tid'])) {
				if($this->pin_by_callback == '3')
					return true;
				else
					$smarty->assign('pin_error', true);
			} else {
				if($this->pin_by_callback == '1')
					$smarty->assign('pin_by_callback',true);
				elseif($this->pin_by_callback == '2')
					$smarty->assign('pin_by_sms',true);
				else
					$smarty->assign('reply_by_email',true);
			}
		}
	}

	/**
	 * Check when order amount has been changed after first payment call
	 *
	 * @param integer $orderAmount
	 * @return none
	 */
	public function orderAmountCheck($orderAmount)
	{
		global $oPlugin;

		if ($orderAmount != $_SESSION['novalnet']['amount']) {
			$_SESSION['fraud_check_error'] = $oPlugin->oPluginSprachvariableAssoc_arr['__NN_amount_fraudmodule_error'];
			$this->novalnetSessionUnset();
			unset($_SESSION[$this->paymentName]['tid']);
			unset($_SESSION['novalnet']);
			header('Location:'.gibShopURL() .'/bestellvorgang.php?editZahlungsart=1&');
			exit;
		}
	}

    /**
	 * Storing response parameters for order comments
	 *
	 * @param array $response
	 * @return none
	 */
	public function fraudModuleComments($response)
	{
		$_SESSION[$this->paymentName]['test_mode'] = (isset($response['test_mode']) && !empty($response['test_mode'])) ? $response['test_mode'] : '';
		$_SESSION[$this->paymentName]['tid']    = $response['tid'];
		$_SESSION['novalnet']['amount'] = $response['amount'];
		$_SESSION[$this->paymentName]['currency'] = $response['currency'];

		if (in_array($this->paymentName, $this->invoicePayments)) {
			$_SESSION[$this->paymentName]['due_date'] = $response['due_date'];
			$_SESSION[$this->paymentName]['invoice_iban'] = $response['invoice_iban'];
			$_SESSION[$this->paymentName]['invoice_bic'] = $response['invoice_bic'];
			$_SESSION[$this->paymentName]['invoice_bankname'] = $response['invoice_bankname'];
			$_SESSION[$this->paymentName]['invoice_bankplace'] = $response['invoice_bankplace'];
			$_SESSION[$this->paymentName]['invoice_account'] = $response['invoice_account'];
			$_SESSION[$this->paymentName]['invoice_bankcode'] = $response['invoice_bankcode'];
		}
	}
}
