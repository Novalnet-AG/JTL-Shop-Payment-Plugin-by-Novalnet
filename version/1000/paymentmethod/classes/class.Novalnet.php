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
 * Script : class.Novalnet.php
 *
 */

require_once( PFAD_ROOT . PFAD_INCLUDES_MODULES . 'PaymentMethod.class.php' );

if (class_exists('NovalnetGateway')) {
	require_once('class.NovalnetInterface.php');
	require_once('class.NovalnetValidation.php');
}

class NovalnetGateway extends PaymentMethod
{
	public $redirectPayments = array('novalnet_banktransfer', 'novalnet_ideal', 'novalnet_paypal', 'novalnet_eps');
	public $formPayments     = array('novalnet_cc', 'novalnet_sepa');
	public $invoicePayments	 = array('novalnet_invoice','novalnet_prepayment');
	public $paymentName;

	const SECOND_CALL_URL = '://payport.novalnet.de/nn_infoport.xml';
	const API_CALL_URL = '://payport.novalnet.de/paygate.jsp';

	/**
	 *
	 * Constructor
	 *
	 */
	function __construct()
	{
		if (isset($_REQUEST['bestellung']) && !empty($_REQUEST['bestellung'])) {
			$this->userSubscriptionFrontend($_REQUEST['bestellung']);
		}
	}

	/**
	 * Sets the payment key for the all the novalnet payments
	 *
	 * @param  string $paymentMethod
	 * @return integer
	 */
	public function setPaymentKey($paymentMethod = '')
	{
		$paymentMethod = empty($paymentMethod) ? $this->paymentName : $paymentMethod;
		$key = array(
			'novalnet_cc'           => 6,
			'novalnet_prepayment'   => 27,
			'novalnet_invoice'      => 27,
			'novalnet_banktransfer' => 33,
			'novalnet_paypal'       => 34,
			'novalnet_sepa'		    => 37,
			'novalnet_ideal'        => 49,
			'novalnet_eps'		    => 50
		);
		return $key[$paymentMethod];
	}

    /**
	 * Sets the payment redirection URL and method name
	 *
	 * @param  bool $paymentName
	 * @return string
	 */
	public function setPaymentConfiguration($paymentName = false)
	{
		$payment = array(
		  'novalnet_prepayment'   => array('url' => '://payport.novalnet.de/paygate.jsp', 'name' => 'PREPAYMENT'),
		  'novalnet_invoice'      => array('url' => '://payport.novalnet.de/paygate.jsp', 'name' => 'INVOICE'),
		  'novalnet_cc'           => array('url' => ($this->cc3d_active_mode) ? '://payport.novalnet.de/global_pci_payport' :'://payport.novalnet.de/paygate.jsp', 'name' => 'CREDITCARD'),
		  'novalnet_paypal'       => array('url' => '://payport.novalnet.de/paypal_payport', 'name' => 'PAYPAL'),
		  'novalnet_ideal'        => array('url' => '://payport.novalnet.de/online_transfer_payport', 'name' => 'IDEAL'),
		  'novalnet_banktransfer' => array('url' => '://payport.novalnet.de/online_transfer_payport', 'name' => 'ONLINE_TRANSFER'),
		  'novalnet_eps'		  => array('url' => '://payport.novalnet.de/eps_payport', 'name' => 'EPS'),
		  'novalnet_sepa'         => array('url' => '://payport.novalnet.de/paygate.jsp', 'name' => 'DIRECT_DEBIT_SEPA')
		);

		return ($paymentName ? $payment[$this->paymentName]['name'] : NovalnetValidation::httpUrlScheme() . trim( $payment[$this->paymentName]['url'] ));
	}

	/**
	 * Assign the configuration values
	 *
	 * @param  none
	 * @return none
	 */
	public function doAssignConfigVarsToMembers()
	{
		global $oPlugin;
		$this->nnVersion = $oPlugin->nVersion;
		$config = array('vendorid', 'productid', 'authcode', 'tariffid', 'tariff_period', 'tariff_period2_amount', 'tariff_period2','proxy','gateway_timeout', 'confirm_order_status', 'cancel_order_status', 'subscription_order_status', $this->paymentName . '_set_order_status',$this->payment . '_callback_status','paypal_pending_status',$this->paymentName . '_payment_reference1',$this->paymentName . '_payment_reference2',$this->paymentName . '_payment_reference3',$this->paymentName . '_reference1', $this->paymentName . '_reference2', $this->paymentName . '_enablemode',$this->paymentName . '_testmode','manual_check_limit','referrerid','autorefill','key_password',$this->paymentName . '_pin_by_callback', $this->paymentName . '_pin_amount');

		if ($this->paymentName == 'novalnet_cc')
			array_push($config,'cc3d_active_mode','cc_valid_yearlimit','cc_amex_accept');
		elseif ($this->paymentName == 'novalnet_sepa')
			array_push($config,'sepa_due_date','sepa_refill');
		elseif ($this->paymentName == 'novalnet_invoice')
			$config[] = 'invoice_duration';

		foreach ($config as $configuration) {
				$val = (strpos($configuration,$this->paymentName) !== false) ? str_replace($this->paymentName.'_','',$configuration) : $configuration;

			if (isset($oPlugin->oPluginEinstellungAssoc_arr[$configuration]))
				$this->$val = trim($oPlugin->oPluginEinstellungAssoc_arr[$configuration]);
		}
	}


	/**
	 * Set order type
	 *
	 * @param  none
	 * @return bool
	 */
	public function returnOrderType()
	{
		return (strpos(basename( $_SERVER['REQUEST_URI'] ), 'bestellab_again.php' ) === false ? false : true);
	}

	/**
	 * Set return urls for redirection payments
	 *
	 * @param  array $params
	 * @param  bool  $orderUpdate
	 * @param  array $paymentOnComplete
	 * @param  array $order
	 * @return none
	 */
	public function setReturnUrls(&$params, $orderUpdate, $paymentOnComplete, $order)
	{
		$paymentHash = $this->generateHash($order);
		if (($_SESSION['Zahlungsart']->nWaehrendBestellung == 0 && !$orderUpdate ) || ($orderUpdate && $paymentOnComplete->nWaehrendBestellung != '')) {
			$_SESSION['novalnet']['nWaehrendBestellung'] = 'Nein';
			$params['orderNo']    = $order->cBestellNr;
			$params['cReturnURL'] = gibShopURL() . '/bestellabschluss.php?i=' . $paymentHash;
			$params['cFailureURL']= gibShopURL() . '/novalnet_return.php';
		} else {
			$params['cReturnURL'] = $this->getNotificationURL($paymentHash) . '&sh=' . $paymentHash;
		}
	}

	/**
	 * Build basic parameters to server
	 *
	 * @param  array $data
	 * @param  float $orderAmount
	 * @param  string $uniqueid
	 * @return none
	 */
	public function buildBasicParams(&$data, $orderAmount, $uniqueid)
	{
		list ($this->vendorid, $this->authcode, $this->key_password) = $this->getAffiliateDetails();
		$amount = ( $orderAmount * 100);
		$manualLimitParam = $this->doCheckManualCheckLimit( $amount );
			$data['auth_code']  = $this->authcode;
			$data['product']    = $this->productid;
			$data['tariff']     = $this->tariffid;
			$data['test_mode']  = $this->testmode;
			$data['amount']	    = $amount;
		if (in_array($this->paymentName, $this->redirectPayments)) {
			$data['uniqid']     = $uniqueid;
			$data['hash']       = $this->generateHashValue($this->generateEncodeArray($data));
		} else {
			if( $manualLimitParam == 1 )
			    $data['on_hold'] = 1;
			if ( $this->cc3d_active_mode )
				$data['encoded_amount'] = $this->generateEncode($amount);
		}
			$data['vendor']     = $this->vendorid;
			$data['key'] 		= $this->setPaymentKey();
	}

	/**
	 * Build common parameters to server
	 *
	 * @param  array $data
	 * @param  string $currency
	 * @param  array $customer
	 * @return none
	 */
	public function buildCommonParams(&$data, $currency, $customer)
	{
		$language       = self::getShopLanguage();
		list ( $firstName, $lastName, $address, $city ) = $this->getCustomerInfo( $customer );
		$data['currency']           = $currency;
		$data['remote_ip']          = ( self::getRealIpAddr() == '::1' ) ? '127.0.0.1' : self::getRealIpAddr();
		$data['first_name']         = ( !empty( $firstName ) ) ? $firstName : $lastName;
		$data['last_name']          = ( !empty( $lastName ) ) ? $lastName : $firstName;
		$data['gender']             = 'u';
		$data['email']              = ( $this->pin_by_callback == '3' && isset( $_SESSION[$this->paymentName]['nn_mail']) && !$this->cc3d_active_mode ) ? trim( $_SESSION[$this->paymentName]['nn_mail'] ) : trim( $customer->cMail );
		$data['street']             = $address;
		$data['search_in_street']   = 1;
		$data['city']               = $city;
		$data['zip']                = $customer->cPLZ;
		$data['language']           = $language;
		$data['lang']               = $language;
		$data['country_code']       = $customer->cLand;
		$data['country']            = $customer->cLand;
		$data['tel']                = ( $this->pin_by_callback == '1' && isset( $_SESSION[$this->paymentName]['nn_telnumber']) && !$this->cc3d_active_mode ) ? $_SESSION[$this->paymentName]['nn_telnumber'] : $customer->cTel;
		$data['mobile']             = ( $this->pin_by_callback == '2' && isset( $_SESSION[$this->paymentName]['nn_mob_number']) && !$this->cc3d_active_mode ) ? $_SESSION[$this->paymentName]['nn_mob_number'] : $customer->cTel;
		$data['customer_no']        = $customer->nRegistriert == '0' ? 'guest' : $customer->kKunde;
		$data['use_utf8']           = 1;
		$data['system_name']        = 'jtlshop';
		$data['system_version']     = $this->getFormattedVersion( getJTLVersionFile() ) . '_NN_10.0.0';
		$data['system_url']         = gibShopURL();
		$data['system_ip']          = ( $_SERVER['SERVER_ADDR'] == '::1' ) ? '127.0.0.1' : $_SERVER['SERVER_ADDR'];
		$data['payment_type']       = $this->setPaymentConfiguration( true );
	}

	/**
	 * Build additional parameters to server
	 *
	 * @param  array $data
	 * @param  array $params
	 * @return none
	 */
	public function buildAdditionalParams(&$data, $params)
	{
		$novalnetValidation = new NovalnetValidation();

		if (!empty($this->referrerid) && NovalnetValidation::isDigits($this->referrerid)) {
			$data['referrer_id']      = $this->referrerid;
		}

		if (!empty($this->tariff_period)) {
			$data['tariff_period']    = $this->tariff_period;
		}

		if (!empty($this->tariff_period2_amount) && NovalnetValidation::isDigits($this->tariff_period2_amount) && !empty($this->tariff_period2)) {
			$data['tariff_period2']   = $this->tariff_period2;
			$data['tariff_period2_amount']	= $this->tariff_period2_amount;
		}

		if ($this->paymentName == 'novalnet_cc') {
			$data['cc_type']            =  '';
			$data['cc_holder']          =  '';
			$data['cc_no']              =  '';
			$data['cc_exp_month']       =  '';
			$data['cc_exp_year']        =  '';
			$data['cc_cvc2']            =  $_SESSION[$this->paymentName]['nn_cvvnumber'];
			$data['pan_hash']           =  $_SESSION[$this->paymentName]['cc_pan_hash'];
			$data['unique_id']          =  $_SESSION[$this->paymentName]['nn_unique_id'];

			if ($this->cc3d_active_mode) {
				$data['session']            = session_id();
				$data['return_url']         = $params['cReturnURL'];
				$data['return_method']      = 'POST';
				$data['error_return_url']   = $params['cFailureURL'];
				$data['error_return_method']= 'POST';
				$data['input3']				= 'payment';
				$data['inputval3']		    = $this->paymentName;
			}

		} elseif (in_array($this->paymentName, $this->invoicePayments)) {
			$data['invoice_type']     = 'PREPAYMENT';

			if ($this->paymentName == 'novalnet_invoice') {
				$data['invoice_type'] = 'INVOICE';
				$_SESSION['novalnet']['duedate'] = $this->getInvoiceDuedate();
				if (!empty($_SESSION['novalnet']['duedate']))
					$data['due_date'] = $_SESSION['novalnet']['duedate'];
			}

		} elseif (in_array($this->paymentName, $this->redirectPayments)) {
			$data['session']            = session_id();
			$data['return_url']         = $params['cReturnURL'];
			$data['return_method']      = 'POST';
			$data['error_return_url']   = $params['cFailureURL'];
			$data['error_return_method']= 'POST';
			$data['user_variable_0']    = gibShopURL();
			$data['implementation']     = 'PHP';
			$data['input3']				= 'payment';
			$data['inputval3']		    = $this->paymentName;

			if (!empty($params['orderNo']))
				$data['order_no']       = $params['orderNo'];

		} elseif ($this->paymentName == 'novalnet_sepa') {
			$data['sepa_unique_id']		= $_SESSION[$this->paymentName]['nn_sepaunique_id'];
			$data['sepa_hash']		    = $_SESSION[$this->paymentName]['nn_sepapanhash'];
			$data['bank_account_holder']= $_SESSION[$this->paymentName]['nn_sepaowner'];
			$data['bank_account']		= '';
			$data['bank_code']			= '';
			$data['bic']				= '';
			$data['iban']				= '';
			$data['iban_bic_confirmed'] = 1;
			$data['sepa_due_date'] 		= $this->getSepaDuedate();
		}
		if ($novalnetValidation->isValidFraudCheck($this)) {
			if ($this->pin_by_callback == '1')
				$data['pin_by_callback']  = 1;

			elseif ($this->pin_by_callback == '2')
				$data['pin_by_sms'] = 1;

			elseif ($this->pin_by_callback == '3')
				$data['reply_email_check'] = 1;
		}

		if (!empty($this->reference1)) {
			$data['input1']		= 'reference1';
			$data['inputval1']	= trim(strip_tags($this->reference1));
		}

		if (!empty($this->reference2)) {
			$data['input2']		= 'reference2';
			$data['inputval2']	= trim(strip_tags($this->reference2));
		}
	}

	/**
	 * Unset the novalnet sessions
	 *
	 * @param  none
	 * @return none
	 */
	public function novalnetSessionUnset()
	{
		$sessionArray = array('novalnet_cc','novalnet_sepa','novalnet_invoice');
		foreach ($sessionArray as $val) {
			if ($this->paymentName != $val) {
				unset($_SESSION[$val]);
			}
		}
	}

	/**
	 * Build the Novalnet order comments
	 *
	 * @param array $parsed
	 * @param array $order
	 * @return string
	 */
	public function updateOrderComments($parsed, $order)
	{
		global $oPlugin;

		$comments = '';
		$comments = !empty($_SESSION['kommentar']) ? $_SESSION['kommentar'] . PHP_EOL . PHP_EOL . $order->cZahlungsartName . PHP_EOL : $order->cZahlungsartName . PHP_EOL;

		if (isset($_SESSION[$this->paymentName]['tid'])) {
			$parsed['test_mode']    	= $_SESSION[$this->paymentName]['test_mode'];
			$parsed['tid']          	= $_SESSION[$this->paymentName]['tid'];
			$parsed['due_date']     	= $_SESSION[$this->paymentName]['due_date'];
			$parsed['invoice_iban']  	= $_SESSION[$this->paymentName]['invoice_iban'];
			$parsed['invoice_bic']  	= $_SESSION[$this->paymentName]['invoice_bic'];
			$parsed['invoice_bankname'] = $_SESSION[$this->paymentName]['invoice_bankname'];
			$parsed['invoice_bankplace']= $_SESSION[$this->paymentName]['invoice_bankplace'];
			$parsed['amount']  			= $_SESSION['novalnet']['amount'];
		}
			$parsed['product_id'] = $this->productid;

		if (in_array($this->paymentName, $this->redirectPayments)) {
			$parsed['test_mode'] = $this->generateDecode($parsed['test_mode']);
		}

		if ( !empty($parsed['test_mode']) || !empty($this->testmode) ) {
			$comments .= $oPlugin->oPluginSprachvariableAssoc_arr['__NN_test_order'] . PHP_EOL;
		}
			$comments .= $oPlugin->oPluginSprachvariableAssoc_arr['__NN_tid_label'] . $parsed['tid'] . PHP_EOL;

		if (in_array($this->paymentName,$this->invoicePayments)) {
			$transComments = $this->formInvoicePrepaymentComments($parsed , $order->Waehrung->cISO, $this->paymentName);
			$comments .= $transComments;
		}

		return $comments;
	}

	/**
	 * Form invoice & prepayment payments comments
	 *
	 * @param array  $datas
	 * @param string $currency
	 * @param string $paymentMethod
	 * @param bool   $updateAmount
	 * @return string
	 */
	public function formInvoicePrepaymentComments($datas = array(), $currency, $paymentMethod, $updateAmount = false)
	{
		global $oPlugin;

		if ($updateAmount)
			$oPlugin = new Plugin($oPlugin->kPlugin);

		array_map( 'utf8_decode', $datas );	

		$order_no = !empty($datas['orderNo']) ? $datas['orderNo'] : 'NN_order';
		$duedate = new DateTime($datas['due_date']);
		$transComments  = PHP_EOL . $oPlugin->oPluginSprachvariableAssoc_arr['__NN_invoice_payments_comments'] . PHP_EOL;
		$transComments .= $oPlugin->oPluginSprachvariableAssoc_arr['__NN_invoice_duedate'] . $duedate->format('d.m.Y') . PHP_EOL;
		$transComments .= $oPlugin->oPluginSprachvariableAssoc_arr['__NN_invoice_payments_holder'] . PHP_EOL;
		$transComments .= 'IBAN: ' . $datas['invoice_iban'] . PHP_EOL;
		$transComments .= 'BIC: ' . $datas['invoice_bic'] . PHP_EOL;
		$transComments .= 'Bank: ' . $datas['invoice_bankname'] . ' ' . $datas['invoice_bankplace'] . PHP_EOL;
		$transComments .= $oPlugin->oPluginSprachvariableAssoc_arr['__NN_invoice_payments_amount'] . number_format( $datas['amount'], 2, ',', '' ) . ' ' . $currency . PHP_EOL;

		$referenceParams = self::getPaymentReferenceValues($paymentMethod);
		$refCount = array_count_values($referenceParams);
		$transComments .= (($refCount[1] > 1) ? $oPlugin->oPluginSprachvariableAssoc_arr['__NN_invoice_payments_multiple_reference_text'] : $oPlugin->oPluginSprachvariableAssoc_arr['__NN_invoice_payments_single_reference_text']) . PHP_EOL;

		$i = 0;
		if ($referenceParams['payment_reference1']) {
			$i = $i + 1;
			$transComments .= ( $refCount[1] == 1 ? $oPlugin->oPluginSprachvariableAssoc_arr['__NN_invoice_payments_reference'] : $oPlugin->oPluginSprachvariableAssoc_arr['__NN_invoice_payments_reference_'.$i] ) . 'BNR-'.$datas['product_id'].'-'.$order_no . PHP_EOL;
		}
		if ($referenceParams['payment_reference2']) {
			$i = $i + 1;
			$transComments .= ( $refCount[1] == 1 ? $oPlugin->oPluginSprachvariableAssoc_arr['__NN_invoice_payments_reference'] : $oPlugin->oPluginSprachvariableAssoc_arr['__NN_invoice_payments_reference_'.$i] ) . 'TID ' .$datas['tid'] . PHP_EOL;
		}
		if ($referenceParams['payment_reference3']) {
			$i = $i + 1;
			$transComments .= ( $refCount[1] == 1 ? $oPlugin->oPluginSprachvariableAssoc_arr['__NN_invoice_payments_reference'] : $oPlugin->oPluginSprachvariableAssoc_arr['__NN_invoice_payments_reference_'.$i] ) . $oPlugin->oPluginSprachvariableAssoc_arr['__NN_order_number_text'] . $order_no . PHP_EOL;
		}

		return $transComments;
	}

	/**
	 * Retreiving payment reference parameters for invoice payments
	 *
	 * @param string $paymentMethod
	 * 
	 * @return array $params
	 */
	public function getPaymentReferenceValues($paymentMethod)
	{
		global $oPlugin;

		$paymentReference = array('payment_reference1','payment_reference2','payment_reference3');

		foreach ($paymentReference as $ref) {
			$params[$ref] = $oPlugin->oPluginEinstellungAssoc_arr[$paymentMethod.'_'.$ref];

		}

		return $params;
	}

	/**
	 * For cancelling subscription from front end
	 *
	 * @param string $orderRequest
	 * @return none
	 */
	private function userSubscriptionFrontend($orderRequest)
	{
		global $oPlugin, $DB;

		$subscriptionValue = $DB->executeQuery('SELECT sub.nSubsId,sub.cBestellnummer,sub.cTerminationReason,nov.nStatuswert FROM xplugin_novalnetag_tsubscription_details sub JOIN tbestellung ord ON ord.cBestellNr = sub.cBestellnummer JOIN xplugin_novalnetag_tnovalnet_status nov ON ord.cBestellNr = nov.cNnorderid WHERE ord.kBestellung="'.$orderRequest.'"',1);

		$placeholder = array('__NN_subscription_title','__NN_subscription_reason_error','__NN_select_type','__NN_subscription_reason_error','__NN_sepa_mandate_confirm_btn');

		$subsReasonPlaceholder = array('__NN_subscription_offer_expensive','__NN_subscription_fraud','__NN_subscription_partner_intervened','__NN_subscription_financial_problems','__NN_subscription_content_not_meeting_expectations','__NN_subscription_content_not_sufficient','__NN_subscription_interest_on_test_access','__NN_subscription_page_slow','__NN_subscription_satisfied','__NN_subscription_access_problems','__NN_subscription_others');

		$subsReason = $this->getLanguageText($subsReasonPlaceholder);

		$subscription = $this->getLanguageText($placeholder);

		echo '<script src='. PFAD_PLUGIN . $oPlugin->cVerzeichnis . '/' . PFAD_PLUGIN_VERSION . $oPlugin->nVersion . '/' . PFAD_PLUGIN_PAYMENTMETHOD . 'js/jquery.js></script>
			  <div id="loading_image_div" style="display:none;">
			  <img style="position:fixed;left:50%;top:50%;z-index:10000" src='. PFAD_PLUGIN . $oPlugin->cVerzeichnis . '/' . PFAD_PLUGIN_VERSION . $oPlugin->nVersion . '/' . PFAD_PLUGIN_PAYMENTMETHOD . 'img/loading.gif >
			  </div>';
		if (is_object($subscriptionValue) && $subscriptionValue->nSubsId != '0' && $subscriptionValue->nStatuswert <= 100) {
			$content ="<fieldset>
							<legend>". $subscription['subscription_title'] . "</legend>
							<p>" . $subscription['subscription_reason_error'] ."
								<select id='subscribe_termination_reason'>
										<option value='' disabled selected>". $subscription['select_type'] ."</option>";
											foreach ($subsReason as $value) {
												$content .= "<option value='".$value."'>".$value."</option>";
											}
									$content .="</select>
								</p><br>
							<input type='hidden' id='orderno' value='".$subscriptionValue->cBestellnummer."'>
							<button name='subs_cancel' type='button' class='confirm' id='subs_cancel' onclick='subscription_cancel()'>". $subscription['sepa_mandate_confirm_btn'] ."</button>
					</fieldset>";
			$content = addcslashes($content,"\\\'\"\n\r");
			if ($subscriptionValue->cTerminationReason == NULL) {
				?>
				<script>
					$(document).ready(function()
					{
						$("#content_footer").prev().append('<?php echo $content; ?>');
					});
					function subscription_cancel(){
						var api_orderno = $('#orderno').val();
						var termination_reason = $('#subscribe_termination_reason').val();

						if (termination_reason == '' || termination_reason == null){
							alert('<?php echo $subscription['subscription_reason_error'];?>');
							return false;
						}
						var params = {'orderNo' : api_orderno , 'apiStatus' : 'subsCancellation' , 'subsReason' : termination_reason, 'frontEnd' : 1 }
						ajax_call(params);
					}

					function ajax_call(data)
					{
						$("#loading_image_div").show();
						if ('XDomainRequest' in window && window.XDomainRequest !== null) {
							var xdr = new XDomainRequest(); // Use Microsoft XDR
							var query_val = { apiParams : data };
							var query = $.param(query_val);
							xdr.open('POST', './admin/novalnet_api.php');
							xdr.onload = function (result) {
								$("#loading_image_div").hide();
								alert(result);
								window.location.reload();
							};
							xdr.onerror = function() {
								_result = false;
							};
							xdr.send(query);
						}
						$.ajax({
							url        : './admin/novalnet_api.php',
							type   	   : 'post',
							dataType   : 'html',
							data       : {apiParams:data},
							success    :  function (result)
							{
								$("#loading_image_div").hide();
								alert(result);
								window.location.reload();
							}
						});
					}
				</script>
				<?php
			}
		}
	}

	/**
	 * To update the order comments into database
	 *
	 * @param integer $order
	 * @param string  $reference
	 * @return none
	 */
	public static function addReferenceToComment($order, $reference)
	{
		global $DB;

		$DB->executeQuery("UPDATE tbestellung SET
        cKommentar = CONCAT(cKommentar, '" . $reference . "') WHERE kBestellung = ".$order,1);

		unset($_SESSION['kommentar']);
	}

	/**
	 * Perform postback call to novalnet server
	 *
	 * @param array $parsed
	 * @param string $orderNo
	 * @param string $orderComments
	 * @return none
	 */
	public function postBackCall($parsed, $orderNo, $orderComments)
	{
		global $DB;

		$novalnetValidation = new NovalnetValidation();

		if ((!empty($parsed['tid']) || !empty($_SESSION[$this->paymentName]['tid'])) && !$novalnetValidation->isConfigInvalid($this)) {
			$postData = array(
				'vendor'      => $this->vendorid,
				'product'     => $this->productid,
				'tariff'      => $this->tariffid,
				'auth_code'   => $this->authcode,
				'key'         => $this->setPaymentKey(),
				'status'      => '100',
				'tid'         => (isset($parsed['tid']) && !empty($parsed['tid'])) ? $parsed['tid'] : $_SESSION[$this->paymentName]['tid'],
				'order_no'    => $orderNo
			);

			if (in_array($this->paymentName,$this->invoicePayments)) {
				$postData['invoice_ref'] = 'BNR-' . $postData['product']  . '-' . $postData['order_no'];
				$refComments = str_replace('NN_order', $postData['order_no'], $orderComments);
				$DB->executeQuery('UPDATE tbestellung SET cKommentar = "' . $refComments . '" WHERE cBestellNr ="' . $postData['order_no'] . '"', 1);
			}

			$postData   = http_build_query($postData, '', '&');
			$url        = NovalnetValidation::httpUrlScheme() . '://payport.novalnet.de/paygate.jsp';
			$response   = $this->novalnetTransactionCall( $postData, $url );

			unset( $_SESSION['novalnet'] );
		}
	}

	/**
	 * Finalize the order
	 *
	 * @param array  $order
	 * @param string $paymentHash
	 * @param array  $args
	 * @param array  $response
	 * @return bool
	 */

	public function verifyNotification($order, $paymentHash, $args, $response)
	{
		global $cEditZahlungHinweis;

		if (in_array($this->paymentName, $this->redirectPayments))
			$this->orderComplete();

		if ($response['status'] == 100 || ($this->paymentName == 'novalnet_paypal' && $response['status'] == 90)) {
			$_POST['kommentar'] = $this->updateOrderComments( $response, $order );
			unset($_SESSION['kommentar']);

			return true;
		} else {
			$_SESSION['novalnet']['error'] = utf8_decode($response['status_text']);
			$cEditZahlungHinweis = utf8_decode($response['status_text']);

			return false;
		}
	}

	/**
	 * To insert the order details into novalnet tables
	 *
	 * @param array $response
	 * @param integer $orderValue
	 * @return none
	 */
	public function insertOrderIntoDB($response, $orderValue)
	{
		global $DB;

		$order = new Bestellung($orderValue);

		$tid = (isset($response['tid']) && !empty($response['tid'])) ? $response['tid'] : $_SESSION[$this->paymentName]['tid'];

		if (isset($_SESSION['nn_aff_id']))
			list($this->vendorid,$this->authcode,$this->key_password) = $this->getAffiliateDetails();

		$newLine = "<br/>";
		$apiStatCode  = $this->transactionStatus($tid, $order->cBestellNr);

		$insertOrder->cNnorderid       = $order->cBestellNr;
		$insertOrder->cKonfigurations  = serialize(array('vendor' => $this->vendorid, 'auth_code' => $this->authcode, 'product' => $this->productid, 'tariff' => $this->tariffid, 'proxy' => $this->proxy));
        $insertOrder->nNntid 		   = $tid;
        $insertOrder->cZahlungsmethode = $this->paymentName;
        $insertOrder->cMail			   = $_SESSION['Kunde']->cMail;
        $insertOrder->nStatuswert 	   = $apiStatCode['status'];
        $insertOrder->cKommentare 	   = $order->cKommentar . $newLine;
        $insertOrder->dDatum 		   = date('d.m.Y H:i:s') . $newLine;
        $insertOrder->cSepaHash        = ($this->paymentName == 'novalnet_sepa' && $_SESSION['Kunde']->nRegistriert != '0') ? $_SESSION[$this->paymentName]['nn_sepapanhash'] : '';
        $insertOrder->nBetrag 		   = ($order->fGesamtsumme) * 100;

		$DB->insertRow('xplugin_novalnetag_tnovalnet_status', $insertOrder);

		if (!in_array($this->paymentName, $this->invoicePayments) && $response['status'] == 100) {
			$insertCallback->cBestellnummer  = $insertOrder->cNnorderid;
			$insertCallback->dDatum		  	 = date('d.m.Y H:i:s');
			$insertCallback->cZahlungsart 	 = $order->cZahlungsartName;
			$insertCallback->nReferenzTid 	 = $tid;
			$insertCallback->nCallbackAmount = $insertOrder->nBetrag;
			$insertCallback->cWaehrung 		 = isset($response['currency']) ? $response['currency'] : $_SESSION[$this->paymentName]['currency'];

			$DB->insertRow('xplugin_novalnetag_tcallback', $insertCallback);
		}

		if ( in_array($this->paymentName, $this->invoicePayments) ) {
			$insertInvoiceDetails->cBestellnummer   = $insertOrder->cNnorderid;
			$insertInvoiceDetails->nTid  			= $tid;
			$insertInvoiceDetails->nProductId		= $this->productid;
			$insertInvoiceDetails->bTestmodus   	= isset($response['test_mode']) ? $response['test_mode'] : $_SESSION[$this->paymentName]['test_mode'];
			$insertInvoiceDetails->cKontoinhaber  	= 'NOVALNET AG';
			$insertInvoiceDetails->cKontonummer  	= isset($response['invoice_account']) ? $response['invoice_account'] : $_SESSION[$this->paymentName]['invoice_account'];
			$insertInvoiceDetails->cBankleitzahl  	= isset($response['invoice_bankcode']) ? $response['invoice_bankcode'] : $_SESSION[$this->paymentName]['invoice_bankcode'];
			$insertInvoiceDetails->cbankName  		= isset($response['invoice_bankname']) ? $response['invoice_bankname'] : $_SESSION[$this->paymentName]['invoice_bankname'];
			$insertInvoiceDetails->cbankCity  		= isset($response['invoice_bankplace']) ? $response['invoice_bankplace'] : $_SESSION[$this->paymentName]['invoice_bankplace'];
			$insertInvoiceDetails->nBetrag  		= isset($response['amount']) ? $response['amount'] : $_SESSION['novalnet']['amount'];
			$insertInvoiceDetails->cWaehrung  		= isset($response['currency']) ? $response['currency'] : $_SESSION[$this->paymentName]['currency'];
			$insertInvoiceDetails->cbankIban  		= isset($response['invoice_iban']) ? $response['invoice_iban'] : $_SESSION[$this->paymentName]['invoice_iban'];
			$insertInvoiceDetails->cbankBic  		= isset($response['invoice_bic']) ? $response['invoice_bic'] : $_SESSION[$this->paymentName]['invoice_bic'];
			$insertInvoiceDetails->cRechnungDuedate = isset($response['due_date']) ? $response['due_date'] : $_SESSION[$this->paymentName]['due_date'];
			$insertInvoiceDetails->dDatum  			= date('d.m.Y H:i:s');

			$DB->insertRow('xplugin_novalnetag_tpreinvoice_transaction_details', $insertInvoiceDetails);
		}

		if ( isset($apiStatCode['status_additional']) && NovalnetValidation::isDigits($apiStatCode['status_additional'])) {
			$insertSubscription->cBestellnummer = $insertOrder->cNnorderid;
			$insertSubscription->nSubsId 	    = $apiStatCode['status_additional'];
			$insertSubscription->nTid 		    = $tid;
			$insertSubscription->dSignupDate    = date('d.m.Y H:i:s');

			$DB->insertRow('xplugin_novalnetag_tsubscription_details', $insertSubscription);
		}

		if (isset($_SESSION['nn_aff_id'])) {
			$insertAffiliate->nAffId      = $this->vendorid;
			$insertAffiliate->cCustomerId = $order->kKunde;
			$insertAffiliate->nAffOrderNo = $insertOrder->cNnorderid;

			$DB->insertRow('xplugin_novalnetag_taff_user_detail', $insertAffiliate);
		}
	}

	/**
	 * Unset cart and coupon sessions
	 *
	 * @param none
	 * @return none
	 */
	public function unsetSessions()
	{
		global $DB;

		if ($_SESSION['Zahlungsart']->nWaehrendBestellung == 0) {
			if ($_SESSION['Kunde']->nRegistriert === '0') {
				unset($_SESSION['Kunde']);
			} else {
				$DB->executeQuery('DELETE wpp FROM twarenkorbperspos AS wpp LEFT JOIN twarenkorbpers AS wp ON wpp.kWarenkorbPers= wp.kWarenkorbPers WHERE wp.kKunde="' . $customer->kKunde, 4);
			}
			unset($_SESSION['Warenkorb']);
		}
		unset($_SESSION['Kupon']);
	}

	/**
	 * To get a unique string
	 *
	 * @param integer $charLimit
	 * @return string
	 */
	public function getRandomString($charLimit = '30')
	{
		$randomwordarray = explode(',', 'a,b,c,d,e,f,g,h,i,j,k,l,m,1,2,3,4,5,6,7,8,9,0');
		shuffle($randomwordarray);
		return substr(implode($randomwordarray,''), 0, $charLimit);
	}

	/**
	 * To check re-order status
	 *
	 * @param string $orderno
	 * @param string  $paymentType
	 * @return array
	 */
	public function checkOrderOnUpdate($orderno, $paymentType)
	{
		global $oPlugin, $DB;

		$paymentOrderType = $DB->executeQuery("SELECT nWaehrendBestellung FROM tzahlungsart WHERE cModulId LIKE '%$paymentType%'", 1);

		$orderComments = $DB->executeQuery("SELECT cKommentar FROM tbestellung WHERE cBestellNr='".$orderno."'", 1);

		$searchTermArray = array ('Novalnet transaction ID:', 'Novalnet-Transaktions-ID:');
		foreach ($searchTermArray as $searchTerm) {
			if (NovalnetValidation::validateString($orderComments->cKommentar, $searchTerm)) {
				$_SESSION['nn_order'] = $orderno;
				$_SESSION['novalnet']['error'] = $oPlugin->oPluginSprachvariableAssoc_arr['__NN_order_not_sucessful'];
				header('Location:' . gibShopURL() . '/novalnet_return.php');
				exit;
			}
		}

		return $paymentOrderType->nWaehrendBestellung;
	}

	/**
	 * To set the error
	 *
	 * @param none
	 * @return none
	 */
	public function setError()
	{
		global $hinweis;

		$error = isset($_SESSION['novalnet']['error']) ? $_SESSION['novalnet']['error'] : $_SESSION['fraud_check_error'];

		if (!empty($_SESSION['novalnet']['error']) || !empty($_SESSION['fraud_check_error']) ) {
			$hinweis = $error;
				unset($_SESSION['novalnet']['error']);
				unset($_SESSION['fraud_check_error']);
		}
	}

	/**
	 * Used to encode the data
	 *
	 * @param string/double $data
	 * @return string
	 */
	private function generateEncode($data)
	{
		if (!function_exists('base64_encode') or !function_exists('pack') or !function_exists('crc32')) {
		  return'Error: func n/a';
		}

		try {
			$crc = sprintf('%u', crc32($data));
			$data = $crc."|".$data;
			$data = bin2hex($data.$this->key_password);
			$data = strrev(base64_encode($data));
		}
		catch (Exception $e){
		  echo('Error: '.$e);
		}
		return $data;
	}

	/**
	 * To get the encoded array
	 *
	 * @param array $data
	 * @return array
	 */
	private function generateEncodeArray(&$data)
	{
		foreach ($data as $key => $val) {
			$data[$key] = $this->generateEncode($val);
		}
		return $data;
	}

	/**
	 * To generate the hash value
	 *
	 * @param array $h
	 * @return string
	 */
	private function generateHashValue($h) {
		if (isset($_SESSION['nn_aff_id'])) {
			$affDetails = $this->getAffiliateDetails();
			$this->key_password = $affDetails[2];
		}
		if (!$h)
		  return 'Error: no data';
		if (!function_exists('md5')) {
		  return 'Error: func n/a';
		}

		return md5( $h['auth_code'] . $h['product'] . $h['tariff'] . $h['amount'] . $h['test_mode'] . $h['uniqid'] . strrev($this->key_password));
	}

	/**
	 * Used to decode the data
	 *
	 * @param string/bool $data
	 * @param bool $redirectOnCancel
	 * @return string
	 */
	public function generateDecode($data, $redirectOnCancel = false)
	{
		$paymentKey = $redirectOnCancel ? trim($GLOBALS['oPlugin']->oPluginEinstellungAssoc_arr['key_password']) : $this->key_password;
		if (isset($_SESSION['nn_aff_id'])) {
			$affDetails = $this->getAffiliateDetails();
			$paymentKey = $affDetails[2];
		}
		if (!function_exists('base64_decode') or !function_exists('pack') or !function_exists('crc32')) {
			return 'Error: func n/a';
		}
		try {
		$data = base64_decode(strrev($data));
		$data = pack("H".strlen($data), $data);
		$data = substr($data, 0, stripos($data, $paymentKey));
		$pos  = strpos($data, "|");

			if ($pos === false) {
				return("Error: CKSum not found!");
			}
			$crc    = substr($data, 0, $pos);
			$value  = trim(substr($data, $pos+1));
			if ($crc !=  sprintf('%u', crc32($value))) {
				return("Error; CKSum invalid!");
			}
		return $value;
		}
		catch (Exception $e) {
			echo('Error: '.$e);
		}
	}

	/**
	 * To check hash from response
	 *
	 * @param array $request
	 * @return bool
	 */
	private function checkHash($request)
	{
		if (!$request) return false; #'Error: no data';
			$h['auth_code']   = $request['auth_code'];
			$h['product']  	  = $request['product'];
			$h['tariff']      = $request['tariff'];
			$h['amount']      = $request['amount'];
			$h['test_mode']   = $request['test_mode'];
			$h['uniqid']      = $request['uniqid'];

		if ($request['hash2'] != $this->generateHashValue($h)) {
			return false;
		}

		return true;
	}

	/**
	 * Adds the sucessful payment method into the shop
	 *
	 * @param object $order
	 * @param bool $paypalPending
	 * @return none
	 */
	public function changeOrderStatus($order, $paypalPending)
	{
		$incomingPayment->fBetrag = $order->fGesamtsummeKundenwaehrung;
		$incomingPayment->cISO = $order->Waehrung->cISO;
		$this->addIncomingPayment($order, $incomingPayment);
		$this->setOrderStatus($order->cBestellNr, ($paypalPending ? $this->paypal_pending_status : $this->set_order_status));
	}

	/**
	 * Sets the order status
	 *
	 * @param string $orderNo
	 * @param string $status
	 * @return none
	 */
	public static function setOrderStatus($orderNo, $status)
	{
		global $DB;

		$updateStatus = "UPDATE tbestellung SET dBezahltDatum = now(),
                    cStatus= '" . constant($status) . "' WHERE cBestellNr = '" . $orderNo . "'";

		$DB->executeQuery($updateStatus,4);
	}

	/**
	 * Checks & assigns manual limit
	 *
	 * @param double $amount
	 * @return bool
	 */
	private function doCheckManualCheckLimit($amount)
	{
		$tidOnhold = 0;

		if ($this->manual_check_limit && NovalnetValidation::isDigits($this->manual_check_limit) && $amount >= $this->manual_check_limit && (in_array($this->paymentName,$this->formPayments) || $this->paymentName == 'novalnet_invoice')) {
			$tidOnhold = 1;
		}
		return $tidOnhold;
	}

	/**
	 * To get the sepa duration in days
	 *
	 * @param none
	 * @return integer
	 */
	private function getSepaDuedate()
	{
		$sepaDate = '';
		$sepaDate = ( NovalnetValidation::isDigits($this->sepa_due_date) && $this->sepa_due_date > 6 ) ? date('Y-m-d', strtotime('+' .$this->sepa_due_date. 'days')) : date('Y-m-d', strtotime('+7 days'));
		return $sepaDate;
	}

    /**
	 * Valid year limit for expiry year
	 *
	 * @param none
	 * @return array
	 */
    public function getValidYearLimit()
    {
		$yearValue = getdate();
		$yearLimit = !empty($this->cc_valid_yearlimit) ? $this->cc_valid_yearlimit : 25;
		for ($i = $yearValue['year'], $j = ($i + $yearLimit); $i < $j; $i++) {
			$ccYear[$i] = $i;
		}
		return $ccYear;
	}

	/**
	 * Order complete process on redirects
	 *
	 * @param none
	 * @return none
	 */
	public function orderComplete()
	{
		global $oPlugin;
		$response = $_REQUEST;
		$order = $_SESSION['novalnet']['order'];

		if ( $response['status'] == 100 || ( $response['status'] == 90 && $response['inputval3'] == 'novalnet_paypal')) {
			if ($response['inputval3'] != 'novalnet_cc') {
				if (!$this->checkHash ($response)) {
					$_SESSION['novalnet']['error'] = html_entity_decode($oPlugin->oPluginSprachvariableAssoc_arr['__NN_hash_error']);
					if($_SESSION['Zahlungsart']->nWaehrendBestellung == 0){
						header('Location:' . gibShopURL() . '/novalnet_return.php');
						exit;
					}
					header('Location:' . gibShopURL() . '/bestellvorgang.php?editZahlungsart=1');
					exit;
				}
			}
			if ( isset($_SESSION['novalnet']['nWaehrendBestellung']) && $_SESSION['novalnet']['nWaehrendBestellung'] == 'Nein' )
				$this->afterPaymentProcess($order, $response);
		} else {
			$_SESSION['novalnet']['error'] = utf8_decode($response['status_text']);
		}
	}

	/**
	 * Get panhash from database for sepa payment
	 *
	 * @param none
	 * @return string
	 */
	public function getSepaRefillHash()
	{
		global $DB;

		$panhash = '';
		$hashValue = $DB->executeQuery('SELECT cSepaHash FROM xplugin_novalnetag_tnovalnet_status WHERE cMail = "'.$_SESSION['Kunde']->cMail.'" ORDER BY kSno DESC LIMIT 1', 1);

		$panhash = ( $this->sepa_refill && $hashValue && !empty($hashValue->cSepaHash)) ? $hashValue->cSepaHash : (($this->autorefill) && !empty($_SESSION[$this->paymentName]['nn_sepapanhash'] ) ? $_SESSION[$this->paymentName]['nn_sepapanhash'] : '');

		return $panhash;
	}

	/**
	 * Get duedate for invoice
	 *
	 * @param none
	 * @return integer
	 */
	private function getInvoiceDuedate()
	{
		$dueDate =  (!empty($this->invoice_duration) && NovalnetValidation::isDigits($this->invoice_duration)) ? date('Y-m-d',strtotime('+'.$this->invoice_duration.' days' )) : '';
		return $dueDate;
	}

	/**
	 * Get gateway timeout limit
	 *
	 * @param none
	 * @return integer
	 */
	private function getGatewayTimeout()
	{
		$timeout = ($this->gateway_timeout && NovalnetValidation::isDigits($this->gateway_timeout)) ? $this->gateway_timeout : '';

		return $timeout;
	}

	/**
	 * Get transaction status
	 *
	 * @param integer $tidVal
	 * @param string $orderNo
	 * @param string  $requestType
	 * @return array
	 */
	public function transactionStatus($tidVal, $orderNo, $requestType = 'TRANSACTION_STATUS', $affiliateDetails)
	{
		list( $vendorId, $authcode, $productId ) = self::getConfigurationDetails($orderNo);

		if (count(array_filter($affiliateDetails)) != 0) {
			$vendorId = $affiliateDetails['vendor'];
			$authcode = $affiliateDetails['authcode'];
		}

		if (isset($_SESSION['nn_aff_id']))
			list( $vendorId, $authcode ) = self::getAffiliateDetails();

		$urlparam  = '<?xml version="1.0" encoding="UTF-8"?><nnxml><info_request><vendor_id>' . $vendorId . '</vendor_id>';
        $urlparam .= '<vendor_authcode>' . $authcode . '</vendor_authcode>';
        $urlparam .= '<request_type>'.$requestType.'</request_type>';
        $urlparam .= '<tid>' . $tidVal . '</tid>';
        if ($requestType == 'TRANSACTION_STATUS') {
			$urlparam .= '<product_id>' . $productId . '</product_id>';
		} else {
			if($requestType == 'PIN_STATUS')
				$urlparam .= '<pin>' . trim($_SESSION['post_array']['nn_pin']) . '</pin>';
			$urlparam .= '<lang>' . self::getShopLanguage() . '</lang>';
		}
        $urlparam .='</info_request></nnxml>';
        $data = self::novalnetTransactionCall( $urlparam , NovalnetValidation::httpUrlScheme(). self::SECOND_CALL_URL);
		$transStatus = array();
		$response = simplexml_load_string($data);
		$response = json_decode(json_encode($response));

		$nnAmount = $response->amount;
		if (strstr($data,'<status>')) {
            $nnStatusCode  = $response->status;
            $nnStatusAdditional = !empty($response->subs_id) ? $response->subs_id : (!empty($response->status_message) ? $response->status_message : '');
            $nnInvoiceDuedate = (!empty($response->invoice_due_date)) ? $response->invoice_due_date : '';
            $nnNextSubsDate = (!empty($response->next_subs_cycle)) ? $response->next_subs_cycle : '';
        } else {
            $nnStatusCode = 0;
            $nnStatusAdditional = !empty($response->pin_status->status_message) ? $response->pin_status->status_message : '';
		}

		$transStatus = array( 'status' => $nnStatusCode, 'status_additional' => $nnStatusAdditional, 'amount' => $nnAmount, 'duedate' => $nnInvoiceDuedate, 'next_subs_date' => $nnNextSubsDate);
		return $transStatus;
	}

	/**
	 * Make curl server call
	 *
	 * @param array $data
	 * @param string $url
	 * @param string $curlProxy
	 * @param integer $curlTimeout
	 * @return array
	 */
	public function novalnetTransactionCall($data, $url, $curlProxy = '', $curlTimeout = '')
	{
		$timeOut = !empty($curlTimeout) ? $this->getGatewayTimeout() : $curlTimeout;
		$proxy = !empty($curlProxy) ? $this->proxy : $curlProxy;
		$ch = curl_init( $url );
		curl_setopt( $ch, CURLOPT_POST, 1 );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 0 );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER,1 );
		curl_setopt( $ch, CURLOPT_TIMEOUT, ($timeOut > 240 ? $timeOut : 240) );
		if ($proxy && !empty($proxy))
			curl_setopt($ch, CURLOPT_PROXY, $proxy);
		$response = curl_exec( $ch );
		curl_close( $ch );
		return $response;
	}

	/**
	 * Get affiliate order details
	 *
	 * @param none
	 * @return array
	 */
	public function getAffiliateDetails()
	{
		global $DB;

		if (!isset($_SESSION['nn_aff_id'])) {
			$affCustomer = $DB->executeQuery("SELECT nAffId FROM xplugin_novalnetag_taff_user_detail WHERE cCustomerId=" . $_SESSION['Kunde']->kKunde . " ORDER BY kId DESC LIMIT 1",1);

			$_SESSION['nn_aff_id'] = $affCustomer->nAffId;
		}

		$affDetails = $DB->executeQuery("SELECT cAffAuthcode, cAffAccesskey FROM xplugin_novalnetag_taffiliate_account_detail WHERE nAffId=" . $_SESSION['nn_aff_id'] . " ORDER BY kId DESC LIMIT 1",1);

		if (is_object($affDetails)) {
			$this->vendorid     = $_SESSION['nn_aff_id'];
			$this->authcode     = $affDetails->cAffAuthcode;
			$this->key_password = $affDetails->cAffAccesskey;
		}

		return array($this->vendorid, $this->authcode, $this->key_password);
	}

	/**
	 * Get configuration parameters from plugin/database
	 *
	 * @param string $orderNo
	 * @return array
	 */
	public static function getConfigurationDetails($orderNo)
	{
		global $DB;

		$oPlugin = self::getPluginObject();
		$nnorder   = $DB->executeQuery("SELECT cKonfigurations FROM xplugin_novalnetag_tnovalnet_status WHERE cNnorderid ='".$orderNo."'", 1);
		$configDb = unserialize($nnorder->cKonfigurations);

		$vendorId = !empty($configDb['vendor']) ? $configDb['vendor'] : trim($oPlugin->oPluginEinstellungAssoc_arr['vendorid']);
		$authcode = !empty($configDb['auth_code']) ? $configDb['auth_code'] : trim($oPlugin->oPluginEinstellungAssoc_arr['authcode']);
		$productId= !empty($configDb['product']) ? $configDb['product'] : trim($oPlugin->oPluginEinstellungAssoc_arr['productid']);
		$tariffId = !empty($configDb['tariff']) ? $configDb['tariff'] : trim($oPlugin->oPluginEinstellungAssoc_arr['tariffid']);
		$proxy    = !empty($configDb['proxy']) ? $configDb['tariff'] : trim($oPlugin->oPluginEinstellungAssoc_arr['proxy']);

		return array($vendorId, $authcode, $productId, $tariffId, $proxy);
	}

	/**
	 * To insert the order details into novalnet table for failure
	 *
	 * @param array $order
	 * @param integer $tid
	 * @param string $paymentType
	 * @param string $comments
	 * @param integer $status
	 * @return bool
	 */
	public static function insertOrderIntoDBForFailure($order, $tid, $paymentType, $comments, $status, $affiliateDetails)
	{
		global $DB;
		$lineBreak = "<br/>";

		list ( $vendorId, $authcode, $productId, $tariffId, $proxy ) = self::getConfigurationDetails($order->cBestellNr);

		if (count(array_filter($affiliateDetails)) != 0) {
			$vendorId = $affiliateDetails['vendor'];
			$authcode = $affiliateDetails['authcode'];
		}

		$insertOrder->cNnorderid        = $order->cBestellNr;
		$insertOrder->cKonfigurations   = serialize(array('vendor' => $vendorId, 'auth_code' => $authcode, 'product' => $productId, 'tariff' => $tariffId, 'proxy' => $proxy));
		$insertOrder->nNntid 			= $tid;
		$insertOrder->cZahlungsmethode	= $paymentType;
		$insertOrder->nStatuswert 	 	= $status;
		$insertOrder->cKommentare 		= $comments . $lineBreak;
		$insertOrder->dDatum 			= date('d.m.Y H:i:s') . $lineBreak;
		$insertOrder->nBetrag 		 	= $order->fGesamtsumme;

		$DB->insertRow('xplugin_novalnetag_tnovalnet_status', $insertOrder);

		return true;
	}

	/**
	 * Get customer billing details
	 *
	 * @param array $customer
	 * @return array
	 */
	public function getCustomerInfo($customer)
	{
		if($customer->nRegistriert == '0') {
			$customer->cVorname = getShopVersion() <= '315' ? html_entity_decode( $customer->cVorname ) : $customer->cVorname;
			$customer->cNachname  = getShopVersion() <= '315' ? html_entity_decode( $customer->cNachname ) : $customer->cNachname;
			$customer->cStrasse   = getShopVersion() <= '315' ? html_entity_decode( $customer->cStrasse ) : $customer->cStrasse;
			$customer->cOrt     = getShopVersion() <= '315' ? html_entity_decode( $customer->cOrt ) : $customer->cOrt;
		}

		return array($customer->cVorname, $customer->cNachname, ($customer->cHausnummer . ',' . $customer->cStrasse), $customer->cOrt);
	}

	/**
	 * Get plugin object
	 *
	 * @param none
	 * @return object
	 */
	public static function getPluginObject()
	{
		return Plugin::getPluginById('novalnetag');
	}

	/**
	 * Returns shop formatted version
	 *
	 * @param integer $value
	 * @return double
	 */
	public function getFormattedVersion($value)
	{
		return number_format($value/100,2,'.','');
	}

	/**
	 * Get language texts for the fields
	 *
	 * @param array $languageFields
	 * @return array
	 */
	public static function getLanguageText($languageFields)
	{
		$PluginObj = self::getPluginObject();

		foreach ($languageFields as $language) {
				$placeholder = str_replace('__NN_','',$language);
				$lang[$placeholder] = $PluginObj->oPluginSprachvariableAssoc_arr[$language];
		}
		return $lang;
	}

	/**
	 * Get currency type for the current order
	 *
	 * @param string $orderNo
	 * @return string
	 */
	public static function getPaymentCurrency($orderNo)
	{
		global $DB;

		$currency  = $DB->executeQuery("SELECT tzahlungseingang.cISO FROM tzahlungseingang
	LEFT JOIN tbestellung ON tzahlungseingang.kBestellung = tbestellung.kBestellung WHERE cBestellNr = '".$orderNo ."'", 1);

		return $currency->cISO;
	}

	/**
	 * Retrieve payment methods stored in the shop
	 *
	 * @param integer $paymentNo
	 * @return array
	 */
	public static function getPaymentMethod($paymentNo)
	{
		global $DB;

		$paymentMethodValue = $DB->executeQuery("SELECT cModulId FROM tzahlungsart WHERE kZahlungsart=".$paymentNo, 1);
		$paymentMethods = array('novalnetkaufaufrechnung'=>'novalnet_invoice', 'novalnetvorauskasse' => 'novalnet_prepayment' , 'novalnetpaypal' => 'novalnet_paypal', 'novalnetkreditkarte' => 'novalnet_cc' ,  'novalnetsofort' => 'novalnet_banktransfer', 'novalnetideal' => 'novalnet_ideal', 'novalneteps' => 'novalnet_eps' , 'novalnetlastschriftsepa' => 'novalnet_sepa');

		foreach ($paymentMethods as $key => $value) {
			if (strpos($paymentMethodValue->cModulId, $key))
				return $value;
		}
	}

	/**
	 * Get current shop language
	 *
	 * @param none
	 * @return string
	 */
	public static function getShopLanguage()
	{
		return $GLOBALS['oSprache']->cISOSprache == 'ger' ? 'DE' : 'EN';
	}

	/**
	 * Get order object
	 *
	 * @param string $orderValue
	 * @return array
	 */
	public static function getOrderObject($orderValue)
	{
		global $DB;

		$order = $DB->executeQuery("SELECT kBestellung FROM tbestellung WHERE cBestellNr = '" . $orderValue . "'",1);
		$orderObj = new Bestellung($order->kBestellung);
		return $orderObj;
	}

	/**
	 * Get real IP address
	 *
	 * @param none
	 * @return mixed
	 */
	public static function getRealIpAddr()
	{
		if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR']) {
			$list = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
			return $list[0];
		}
		else if (isset($_SERVER['HTTP_X_REAL_IP']) && $_SERVER['HTTP_X_REAL_IP']) {
			return $_SERVER['HTTP_X_REAL_IP'];
		}

		return $_SERVER['REMOTE_ADDR'];
	}

	/**
	 * Get payment name from payment settings
	 *
	 * @param integer $paymentNo
	 * @return string
	 */
	public static function getPaymentName($paymentNo)
	{
		global $DB, $oSprache;

		$paymentMethod = gibZahlungsart(intval($paymentNo));
		$paymentName = $DB->executeQuery("SELECT cName FROM tzahlungsartsprache WHERE kZahlungsart = $paymentMethod->kZahlungsart AND cISOSprache = \"" . $oSprache->cISOSprache . "\"",1);

		return $paymentName->cName;
	}
}
$novalnet = new NovalnetGateway();
?>
