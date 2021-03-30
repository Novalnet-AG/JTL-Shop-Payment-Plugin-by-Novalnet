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
 * Script: Novalnet.callback.class.php
 *
 */
require_once( 'includes/globalinclude.php' );
require_once( PFAD_INCLUDES_MODULES . 'PaymentMethod.class.php' );
require_once( PFAD_ROOT. PFAD_CLASSES . 'class.JTL-Shop.Plugin.php' );

/**
 * Handles callback request
 *
 * @param none
 * @return none
 */
function performCallbackExecution()
{
	global $processTestMode, $processDebugMode, $oPlugin, $jtlPaymentClass;
	
	//get order object
	$oPlugin = Plugin::getPluginById( 'novalnetag' );

	require_once( PFAD_PLUGIN . $oPlugin->cPluginID . '/' . PFAD_PLUGIN_VERSION . $oPlugin->nVersion . '/' . PFAD_PLUGIN_PAYMENTMETHOD . PFAD_CLASSES . 'Novalnet.abstract.class.php' );
	require_once( PFAD_INCLUDES . 'mailTools.php' );

	$jtlPaymentClass  = NovalnetGateway::getInstance(); // Get instance of NovalnetGateway class

	$callbackRequestParams = $_REQUEST;
	
	$processTestMode  = $oPlugin->oPluginEinstellungAssoc_arr['callback_testmode']; // Update into false when switching into LIVE MODE
	$processDebugMode = $oPlugin->oPluginEinstellungAssoc_arr['callback_debugmode']; // Update into true to debug mode

	$jtlVendorScript  = new NovalnetVendorScript( $callbackRequestParams ); // Get instance of NovalnetVendorScript class

	// Affiliate process
	if ( !empty( $callbackRequestParams['vendor_activation'] ) )
	{
		$jtlVendorScript->updateAffiliateDatas( $callbackRequestParams ); // Logs affiliate details into database

		$callbackScriptText = 'Novalnet callback script executed successfully with Novalnet account activation information.';
		
		//Send notification mail to Merchant
		$jtlVendorScript->sendMailNotification( array(
			'comments' => $callbackScriptText
		), true );
		$jtlVendorScript->debugError( $callbackScriptText );
	}
	else
	{
		$jtlVendorParams = $jtlVendorScript->arycaptureparams; // Callback request params
		$jtlVendorHistory = $jtlVendorScript->getOrderByIncrementId( $jtlVendorParams ); // Loads the corresponding order object based on request

		$orderId = $jtlVendorHistory->cBestellNr;
		$query = Shop::DB()->query( 'SELECT nBetrag FROM xplugin_novalnetag_tnovalnet_status WHERE nNntid = "' . $jtlVendorParams['shop_tid'] . '"', 1);
		$orderTotalAmount = $query->nBetrag;
		$currencyFormat = NovalnetGateway::getPaymentCurrency( $orderId ); // Retrieves payment currency for request
		
		$query = Shop::DB()->query('SELECT SUM(nCallbackAmount) AS totalAmount FROM xplugin_novalnetag_tcallback WHERE cBestellnummer = "' . $orderId . '"', 1);
		
		$callbackScriptText ='';
		
		$paymentLevel = $jtlVendorScript->getPaymentTypeLevel(); // Determine the payment type level to which the requested payment belongs

		// Cancellation of a Subscription
		if ( $jtlVendorParams['payment_type'] == 'SUBSCRIPTION_STOP' && $jtlVendorParams['status'] == 100 && $jtlVendorParams['tid_status'] == 100 )
		{
			if ( !empty( $jtlVendorParams['termination_reason'] ) ) {
				NovalnetGateway::performDbExecution( 'xplugin_novalnetag_tsubscription_details', 'cTerminationReason = "' . $jtlVendorParams['termination_reason'] . '", dTerminationAt = "' . $jtlVendorParams['termination_at'] . '"', 'nTid="' . $jtlVendorParams['shop_tid'] . '"' ); // Updates the value into the database
			}
			
			$callbackScriptText = PHP_EOL . 'Novalnet Callbackscript received. Subscription has been stopped for the TID: ' . $jtlVendorParams['shop_tid'] . ' on ' . date( 'd.m.Y H:i:s' ) . PHP_EOL . 'Reason for Cancellation: '. $jtlVendorParams['termination_reason'] . PHP_EOL;

			NovalnetGateway::performDbExecution( 'tbestellung', 'cStatus=' . constant( $oPlugin->oPluginEinstellungAssoc_arr['subscription_order_status'] ), 'cBestellNr = "' . $orderId . '"' ); // Updates the value into the database

			$jtlVendorScript->callbackFinalProcess( $callbackScriptText, $jtlVendorHistory, true ); // Completes the callback execution
		}
		
		switch ( $paymentLevel )
		{
			case 2: // Level 2 payments - Types of Collections available
			
				if ( $jtlVendorParams['payment_type'] == 'INVOICE_CREDIT' && $jtlVendorParams['status'] == 100 && $jtlVendorParams['tid_status'] == 100 )
				{
					$orderPaidAmount = $query->totalAmount + $jtlVendorParams['amount'];
					
					if ( $query->totalAmount < $orderTotalAmount )
					{
						$callbackScriptText = PHP_EOL . 'Novalnet Callback Script executed successfully for the TID: ' . $jtlVendorParams['shop_tid'] . ' with amount: ' . gibPreisString( $jtlVendorParams['amount'] /100 ).' '. $currencyFormat .' on ' . date('d.m.Y H:i:s') . '. Please refer PAID transaction in our Novalnet Merchant Administration with the TID:' . $jtlVendorParams['tid'] . PHP_EOL;

						$callbackGreaterAmount = '';
						
						if ( $orderPaidAmount >= $orderTotalAmount )
						{
							if ( $orderPaidAmount > $orderTotalAmount )
							{
								$callbackGreaterAmount = 'Customer has paid more than the Order amount.' . PHP_EOL;
							}

							$paymentName = $jtlPaymentClass->getPaymentMethod( $jtlVendorHistory->kZahlungsart, false );
							
							$jtlPaymentmethod = new PaymentMethod( 'kPlugin_' . $oPlugin->kPlugin . '_' . $paymentName );

							$incomingPayment = new stdClass();
							$incomingPayment->fBetrag = $jtlVendorHistory->fGesamtsumme / 100;
							$incomingPayment->cISO = $currencyFormat;
							$incomingPayment->cHinweis = $jtlVendorParams['shop_tid'];
							$jtlPaymentmethod->name = $jtlVendorHistory->cZahlungsartName;
							$jtlPaymentmethod->addIncomingPayment( $jtlVendorHistory, $incomingPayment ); // Adds the current transaction into the shop's order table

							NovalnetGateway::performDbExecution( 'tbestellung', 'dBezahltDatum = now(), cStatus=' . constant( $oPlugin->oPluginEinstellungAssoc_arr[$jtlVendorHistory->payment_type.'_callback_status'] ), 'cBestellNr = "' . $orderId . '"' ); // Updates the value into the database
						}

						$jtlVendorScript->callbackFinalProcess( $callbackScriptText, $jtlVendorHistory, false, true, $callbackGreaterAmount );

					}
					$jtlVendorScript->debugError( 'Novalnet callback received. Callback Script executed already. Refer Order :' . $orderId );
				}
				break;

			case 1: // Level 1 payments - Types of Chargebacks

				if ( $jtlVendorParams['status'] == 100 && $jtlVendorParams['tid_status'] == 100 )
				{
					$callbackScriptText = in_array( $jtlVendorParams['payment_type'], array( 'CREDITCARD_BOOKBACK', 'RETURN_DEBIT_SEPA', 'PAYPAL_BOOKBACK' ) ) ? PHP_EOL . 'Novalnet callback received. Refund/Bookback executed successfully for the TID: ' . $jtlVendorParams['tid_payment'] . ' amount:'. gibPreisString( $jtlVendorParams['amount'] / 100 ) . ' ' . $currencyFormat .' on '. date('d.m.Y H:i:s') . ' The subsequent TID: ' .$jtlVendorParams['tid'] . PHP_EOL : PHP_EOL . 'Novalnet callback received. Chargeback executed successfully for the TID: ' . $jtlVendorParams['tid_payment'] . ' amount:'. gibPreisString( $jtlVendorParams['amount'] / 100 ) .' ' . $currencyFormat .' on '.date('d.m.Y H:i:s') . ' The subsequent TID: ' .$jtlVendorParams['tid'] . PHP_EOL;

					$jtlVendorScript->callbackFinalProcess( $callbackScriptText, $jtlVendorHistory ); // Completes the callback execution
				}
				break;

			case 0: //level 0 payments - Types of payments
			
				if ( $jtlVendorParams['status'] == 100 && $jtlVendorParams['tid_status'] == 100 )
				{
					if ( $jtlVendorParams['subs_billing'] == 1 )
					{
						$callbackScriptText = PHP_EOL . 'Novalnet Callback Script executed successfully for the Subscription TID: ' . $jtlVendorParams['shop_tid'] . ' with amount '. gibPreisString( $jtlVendorParams['amount'] / 100 ) . ' ' . $currencyFormat .' on '. date( 'd.m.Y H:i:s' ) . '. Please refer PAID transaction in our Novalnet Merchant Administration with the TID:' . $jtlVendorParams['tid'] . PHP_EOL;

						$nextsubsdate = date( 'd.m.Y H:i:s', strtotime( $jtlVendorParams['paid_until'] ) );

						$callbackScriptText .= 'Next Charging date : ' . ( ( !empty( $jtlVendorParams['paid_until'] ) ) ? $nextsubsdate : '' ) . PHP_EOL;

						$jtlVendorScript->callbackFinalProcess( $callbackScriptText, $jtlVendorHistory, true ); // Completes the callback execution
					}
					elseif ( $jtlVendorParams['payment_type'] == 'PAYPAL' )
					{
						if ( $query->totalAmount < $orderTotalAmount )
						{
							$callbackScriptText = PHP_EOL . 'Novalnet Callback Script executed successfully for the TID: ' . $jtlVendorParams['shop_tid'] . ' with amount: ' . gibPreisString( $jtlVendorParams['amount'] / 100 ) .' '.$currencyFormat . ' on ' . date( 'd.m.Y H:i:s' ) . ' Please refer PAID transaction in our Novalnet Merchant Administration with the TID:' . $jtlVendorParams['tid'] . PHP_EOL;

							$paymentName = $jtlPaymentClass->getPaymentMethod( $jtlVendorHistory->kZahlungsart, false );
							
							$jtlPaymentmethod = new PaymentMethod( 'kPlugin_' . $oPlugin->kPlugin . '_' . $paymentName );

							$incomingPayment = new stdClass();
							$incomingPayment->fBetrag = $jtlVendorHistory->fGesamtsumme / 100;
							$incomingPayment->cISO = $currencyFormat;
							$incomingPayment->cHinweis = $jtlVendorParams['shop_tid'];
							$jtlPaymentmethod->name = $jtlVendorHistory->cZahlungsartName;
							$jtlPaymentmethod->addIncomingPayment( $jtlVendorHistory, $incomingPayment ); // Adds the current transaction into the shop's order table

							NovalnetGateway::performDbExecution( 'tbestellung', 'dBezahltDatum = now(), cStatus=' . constant( $oPlugin->oPluginEinstellungAssoc_arr[$jtlVendorHistory->payment_type.'_set_order_status'] ), 'cBestellNr = "' . $orderId . '"' ); // Updates the value into the database
							
							NovalnetGateway::performDbExecution( 'xplugin_novalnetag_tnovalnet_status', 'nStatuswert = "' . $jtlVendorParams['tid_status'] . '"', 'cNnorderid="' . $orderId . '"' ); // Updates the value into the database

							$jtlVendorScript->callbackFinalProcess( $callbackScriptText, $jtlVendorHistory ); // Completes the callback execution
						}
						$jtlVendorScript->debugError( 'Novalnet Callbackscript received. Order already Paid' );
					}
					$error = 'Novalnet Callbackscript received. Payment type ( ' . $jtlVendorParams['payment_type'] . ' ) is not applicable for this process!';
					$jtlVendorScript->debugError( $error );
					break;
				}
				else if ( $jtlVendorParams['status'] != 100 && $jtlVendorParams['subs_billing'] == 1 )
				{
					$cancelReason = $jtlPaymentClass->getResponseText( $jtlVendorParams );
					
					NovalnetGateway::performDbExecution( 'xplugin_novalnetag_tsubscription_details', 'cTerminationReason = "' . $cancelReason . '", dTerminationAt = "' . date('Y-m-d H:i:s') . '"', 'nTid="' . $jtlVendorParams['shop_tid'] . '"' ); // Updates the value into the database

					$callbackScriptText = PHP_EOL . 'Novalnet Callbackscript received. Subscription has been stopped for the TID: ' . $jtlVendorParams['shop_tid'] . ' on ' . date( 'd.m.Y H:i:s' ) . PHP_EOL . 'Reason for Cancellation: ' . $cancelReason . PHP_EOL;

					NovalnetGateway::performDbExecution( 'tbestellung', 'cStatus=' . constant( $oPlugin->oPluginEinstellungAssoc_arr['subscription_order_status'] ), 'cBestellNr = "' . $orderId . '"' ); // Updates the value into the database

					$jtlVendorScript->callbackFinalProcess( $callbackScriptText, $jtlVendorHistory, true ); // Completes the callback execution
				}
		}

		/*
		* Error section : When status executing other than 100
		*/
		$jtlVendorScript->debugError('Novalnet callback received. Status is not valid.');
	}
}

/**
 * Class NovalnetVendorScript
 */
class NovalnetVendorScript {

	/**
	 * @Type of payments available - Level : 0
     * @var array
     */
	protected $aryPayments = array( 'CREDITCARD','INVOICE_START', 'DIRECT_DEBIT_SEPA','GUARANTEED_DIRECT_DEBIT_SEPA', 'GUARANTEED_INVOICE_START','PAYPAL','ONLINE_TRANSFER','IDEAL','EPS','PAYSAFECARD', 'GIROPAY' );

	/**
	 * @Type of Chargebacks available - Level : 1
     * @var array
     */
	protected $aryChargebacks = array( 'RETURN_DEBIT_SEPA','REVERSAL','CREDITCARD_BOOKBACK','CREDITCARD_CHARGEBACK','REFUND_BY_BANK_TRANSFER_EU', 'PAYPAL_BOOKBACK' );

	/**
	 * @Type of CreditEntry payment and Collections available - Level : 2
     * @var array
     */
	protected $aryCollection = array( 'INVOICE_CREDIT','GUARANTEED_INVOICE_CREDIT','CREDIT_ENTRY_CREDITCARD','CREDIT_ENTRY_SEPA','DEBT_COLLECTION_SEPA','DEBT_COLLECTION_CREDITCARD' );

	/**
     * @var array
     */
	protected $callbackRequestParams = array();
	
	/**
	 * @IP-Address Novalnet IP, is a fixed value, DO NOT CHANGE!!!!!
     * @var array
     */
	protected $ipAllowed = array( '195.143.189.210','195.143.189.214' );

	/**
     * @var array
     */
	protected $aPaymentTypes = array(
		'novalnet_invoice'    	=> array( 'INVOICE_CREDIT','GUARANTEED_INVOICE_START','INVOICE_START', 'SUBSCRIPTION_STOP' ),
		'novalnet_prepayment'   => array( 'INVOICE_CREDIT','INVOICE_START', 'SUBSCRIPTION_STOP' ),
		'novalnet_paypal'    	=> array( 'PAYPAL', 'SUBSCRIPTION_STOP', 'PAYPAL_BOOKBACK' ),
		'novalnet_banktransfer' => array( 'ONLINE_TRANSFER' ),
		'novalnet_cc'      		=> array( 'CREDITCARD', 'CREDITCARD_BOOKBACK', 'CREDITCARD_CHARGEBACK', 'SUBSCRIPTION_STOP' ),
		'novalnet_ideal'        => array( 'IDEAL' ),
		'novalnet_sepa'         => array( 'DIRECT_DEBIT_SEPA', 'GUARANTEED_DIRECT_DEBIT_SEPA', 'RETURN_DEBIT_SEPA', 'SUBSCRIPTION_STOP' ),
		'novalnet_eps'			=> array( 'EPS' ),
		'novalnet_giropay'      => array( 'GIROPAY' )
	);

	/**
     * @var array
     */
	protected $paramsRequired = array();

	/**	
     * @var array
     */
	protected $affParamsRequired = array();

	/**
	 *
	 * Constructor
	 *
	 */
	public function __construct( $aryCapture )
	{
		// Required mandatory callback params
		$this->paramsRequired = array( 'vendor_id', 'tid', 'payment_type', 'status', 'tid_status' );

		// Required mandatory affiliate callback params
		$this->affParamsRequired = array( 'vendor_id', 'vendor_authcode', 'aff_id', 'aff_authcode','aff_accesskey', 'product_id' );

		if ( !empty( $aryCapture['subs_billing'] ) ) {
			array_push( $this->paramsRequired, 'signup_tid' );
		} elseif ( isset( $aryCapture['payment_type'] ) && in_array( $aryCapture['payment_type'], array_merge( $this->aryChargebacks, array( 'INVOICE_CREDIT' ) ) ) ) {
			array_push( $this->paramsRequired, 'tid_payment' );
		}

		// Validates the callback params before processing
		$this->arycaptureparams = self::validateCaptureParams( $aryCapture );
	}

	/**
	 * Throws callback script texts and errors
	 *
	 * @param string $errorMsg
	 * @param bool   $mailText
	 * @param bool   $forceDisplay
	 * @return none
	 */
	public function debugError( $errorMsg, $mailText = false, $forceDisplay = false )
	{
		global $processDebugMode;
		
		if ( $processDebugMode || $forceDisplay ) {
			echo $errorMsg;
		}
		
		if ( !$mailText )
			exit();
	}

	/**
	 * Get the payment type level for the request payment
	 *
	 * @param none
	 * @return integer/bool
	 */
	public function getPaymentTypeLevel()
	{
		if ( in_array( $this->arycaptureparams['payment_type'], $this->aryPayments ) ) {
			return 0;
		} else if ( in_array( $this->arycaptureparams['payment_type'], $this->aryChargebacks ) ) {
			return 1;
		} else if ( in_array( $this->arycaptureparams['payment_type'], $this->aryCollection ) ) {
			return 2;
		}
		return false;
	}

	/**
	 * Triggers mail notification to the mail address specified
	 *
	 * @param array $mailDatas
	 * @param bool  $affiliate
	 * @return bool
	 */
	public function sendMailNotification( $mailDatas, $affiliate = false )
	{
		global $oPlugin;

		$emailFrom = Shop::DB()->query('SELECT cMail from tadminlogin',1);
		$emailToAddr = $oPlugin->oPluginEinstellungAssoc_arr['callback_to_address'];
		$emailBccAddr = $oPlugin->oPluginEinstellungAssoc_arr['callback_bcc_address'];

		if ( $oPlugin->oPluginEinstellungAssoc_arr['callback_notification_send'] == 1 && !empty( $mailDatas ) ) {

			//Reporting Email Addresses Settings
			$emailFromAddr = $emailFrom->cMail;
			//sender email addr., manditory, adapt it
			$validEmail = true;

			$emailAddr = explode( ',', $emailToAddr . ',' . $emailBccAddr );
			$emailAddr = array_map( 'trim', $emailAddr );

			foreach ( $emailAddr as $addr ) {
				if ( !empty( $addr ) && !valid_email ( $addr ) ) {
					$validEmail = false;
					break;
				}
			}

			if ( $validEmail && !empty( $emailToAddr ) ) {
				$emailSubject  = 'Novalnet Callback script notification'; //adapt if necessary;
				$emailBody     = ( !$affiliate ) ? 'Order : ' . $mailDatas['orderNo'] . ' <br/> Message : ' . $mailDatas['comments'] : ' <br/> Message : ' . $mailDatas['comments'];//Email text's 1. line, can be let blank, adapt for your need
				$emailBody     = str_replace( '<br />', PHP_EOL , $emailBody );
				$emailFromName = 'Novalnet'; // Sender name, adapt
				$headers  = 'Content-Type: text/html; charset=iso-8859-1'. "\r\n";
				$headers .= "From: " . $emailFromName . " <" . $emailFromAddr . ">\r\n";
	  
				if ( !empty($emailBccAddr) )
					$headers .= 'BCC: ' . $emailBccAddr . "\r\n";

				$sendmail = mail( $emailToAddr , $emailSubject, $emailBody, $headers );

				if ( $sendmail )
					self::debugError( 'Mail sent!', true );
				else
					self::debugError( 'Mail not sent!', true );
			}
			else
				self::debugError( 'Mail not sent!', true );
			return true;
		}
		return false;
	}

	/**
	 * Validates the request parameters
	 *
	 * @param array $callbackRequestParams
	 * @return array
	 */
	public function validateCaptureParams( $callbackRequestParams )
	{
		global $processTestMode, $paramsRequired;

		if ( !in_array( getRealIp(), $this->ipAllowed ) && !$processTestMode ) { // Condition to check whether the callback is called from authorized IP
			self::debugError( 'Unauthorised access from the IP ' . getRealIp(), false, true );
		}

		if ( !empty( $callbackRequestParams ) ) {
			if ( !empty( $callbackRequestParams['vendor_activation'] ) ) {
				self::validateRequiredParameters( $this->affParamsRequired, $callbackRequestParams ); // Validates affiliate parameters
			} else {
				self::validateRequiredParameters( $this->paramsRequired, $callbackRequestParams ); // Validates basic callback parameters

				if ( !in_array( $callbackRequestParams['payment_type'], array_merge( $this->aryCollection, $this->aryChargebacks , $this->aryPayments , array( 'SUBSCRIPTION_STOP' ) ) ) ) {
					self::debugError( 'Novalnet callback received. Payment type[' . $callbackRequestParams['payment_type'] . '] is mismatched!' );
				}

				if ( $callbackRequestParams['payment_type'] != 'SUBSCRIPTION_STOP' && ( !is_numeric( $callbackRequestParams['amount'] ) || $callbackRequestParams['amount'] < 0 ) ) {
					self::debugError( 'Novalnet callback received. The requested amount ('. $callbackRequestParams['amount'] .') is not valid' );
				}

				$tidCheck = array( $callbackRequestParams['tid'] );
				$callbackRequestParams['shop_tid'] = $callbackRequestParams['tid'];

				if ( isset( $callbackRequestParams['subs_billing'] ) && $callbackRequestParams['subs_billing'] != 1 && in_array( $callbackRequestParams['payment_type'], array_merge( $this->aryChargebacks, array( 'INVOICE_CREDIT' ) ) ) ) {
					$tidCheck[] = $callbackRequestParams['tid_payment'];
					$callbackRequestParams['shop_tid'] = $callbackRequestParams['tid_payment'];
				}

				if ( isset( $callbackRequestParams['subs_billing'] ) && $callbackRequestParams['subs_billing'] == 1 || $callbackRequestParams['payment_type'] == 'SUBSCRIPTION_STOP' ) {
					$tidCheck[] = !empty( $callbackRequestParams['signup_tid'] ) ? $callbackRequestParams['signup_tid'] : '';
					$callbackRequestParams['shop_tid'] = !empty( $callbackRequestParams['signup_tid'] ) ? $callbackRequestParams['signup_tid'] : '';
				}
					
				foreach( $tidCheck as $arrTid ) {
					if ( !is_numeric( $arrTid ) || strlen( $arrTid ) != 17 ) {
						self::debugError('Novalnet callback received. Invalid TID [' . $arrTid . '] for Order.');
					}
				}
			}
			return $callbackRequestParams;
		}
		else {
			self::debugError( 'No params passed over!' );
		}
	}

	/**
	 * Get order details from the shop's database
	 *
	 * @param array $aryCaptureValues
	 * @return object
	 */
	public function getOrderByIncrementId( $aryCaptureValues )
	{
		global $jtlPaymentClass;
		
		$order = Shop::DB()->query( 'SELECT cNnorderid FROM xplugin_novalnetag_tnovalnet_status WHERE nNntid = "' . $aryCaptureValues['shop_tid'] . '"', 1);

		$uniqueOrderValue = !empty( $order->cNnorderid ) ? self::getUniqueOrderValue( $order->cNnorderid )  : '';

		$orderNo = ( !empty( $aryCaptureValues['order_no'] ) ) ? $aryCaptureValues['order_no'] : ( !empty( $aryCaptureValues['order_id']) ? $aryCaptureValues['order_id'] : '' );

		if ( empty( $uniqueOrderValue ) )
		{
			if ( $orderNo )
			{
				$order = Shop::DB()->query( 'SELECT kBestellung FROM tbestellung WHERE cBestellNr = "' . $orderNo . '"', 1);
				
					if ( empty( $order->kBestellung ) ) {
						self::debugError( 'Transaction mapping failed' );
					}

					$uniqueOrderValue = self::getUniqueOrderValue( $orderNo ); // Gets unique order ID for the particular order stored in shop database

					$order = new Bestellung( $uniqueOrderValue );
					
					if ( $orderNo != $order->cBestellNr ) {
						self::debugError( 'Novalnet callback received. Order Number is not valid.' );
					}
			} else {
				self::debugError( 'Transaction mapping failed' );
			}
		}

		$order = new Bestellung( $uniqueOrderValue ); // Loads order object from shop
		
		if ( $orderNo && $order->cBestellNr != $orderNo )
			self::debugError( 'Order number not valid' );
				
		$order->payment_type = $jtlPaymentClass->getPaymentMethod( $order->kZahlungsart );
		$order->fGesamtsumme = ( $order->fGesamtsumme * 100 );

		if ( ( !array_key_exists( $order->payment_type, $this->aPaymentTypes ) ) || !in_array($aryCaptureValues['payment_type'], $this->aPaymentTypes[$order->payment_type] ) )
			self::debugError( 'Novalnet callback received. Payment type [' . $aryCaptureValues['payment_type'] . '] is mismatched!' );

		if ( empty( $order->cKommentar ) )
			$this->handleCommunicationBreak( $order, $aryCaptureValues ); // Handles communication failure scenario

		return $order;
	}

	/**
	 * To get order object's kBestellung value
	 *
	 * @param array $orderNo
	 * @return integer
	 */
	public function getUniqueOrderValue( $orderNo ) 
	{
		$uniqueValue = Shop::DB()->query( 'SELECT kBestellung FROM tbestellung WHERE cBestellNr = "' . $orderNo . '"', 1);

		return $uniqueValue->kBestellung;
	}

	/**
	 * To check the required parameters is present or not
	 *
	 * @param array $paramsRequired
	 * @param array $callbackRequestParams
	 * @return bool
	 */
	public function validateRequiredParameters( $paramsRequired, $callbackRequestParams )
	{
		foreach ( $paramsRequired as $k => $v ) {
			if ( empty( $callbackRequestParams[$v] ) ) {
				self::debugError( 'Required param (' . $v . ') missing!' );
			}
		}
		return true;
	}

	/**
	 * Handling communication breakup
	 *
	 * @param array $order
	 * @param array $callbackRequestParams
	 * @return none
	 */
	public function handleCommunicationBreak( $order, $callbackRequestParams )
	{
		global $oPlugin, $jtlPaymentClass;
		
		$orderId = $order->cBestellNr;

		$txn_message = $order->cZahlungsartName . PHP_EOL;
		if ( !empty( $callbackRequestParams['test_mode'] ) )
			$txn_message .= ( ( $order->kSprache == 1 ) ? 'Testbestellung' : 'Test order' ) . PHP_EOL;
		$txn_message.= ( ( $order->kSprache == 1 ) ? 'Novalnet-Transaktions-ID:' : 'Novalnet transaction ID:' ) . $callbackRequestParams['shop_tid']  . PHP_EOL;

		$paymentName = $jtlPaymentClass->getPaymentMethod( $order->kZahlungsart, false );

		$jtlPaymentmethod = new PaymentMethod( 'kPlugin_' . $oPlugin->kPlugin . '_' . $paymentName ); // Instance of class PaymentMethod

		if ( $callbackRequestParams['status'] == 100 && in_array( $callbackRequestParams['tid_status'], array( 90, 91, 98, 99, 100 ) ) ) { // Condition to check communication failure for the payment success
			$currencyFormat = NovalnetGateway::getPaymentCurrency( $orderId ); // Retrieves payment currency for request
			
			$incomingPayment = new stdClass();
			$incomingPayment->fBetrag = $order->fGesamtsumme / 100;
			$incomingPayment->cISO = $currencyFormat;
			$incomingPayment->cHinweis = $callbackRequestParams['shop_tid'];
			$jtlPaymentmethod->name = $order->cZahlungsartName;
			$jtlPaymentmethod->addIncomingPayment( $order, $incomingPayment ); // Adds the current transaction into the shop's order table
							
			if ( $order->payment_type == 'novalnet_paypal' && $callbackRequestParams['tid_status'] == 90 ) {
				NovalnetGateway::performDbExecution( 'tbestellung', 'dBezahltDatum = now(),
				cKommentar = CONCAT(cKommentar, "' . $txn_message . '"), cStatus=' . constant( $oPlugin->oPluginEinstellungAssoc_arr['paypal_pending_status'] ), 'cBestellNr = "' . $orderId . '"' ); // Updates the value into the database
			} else {
				NovalnetGateway::performDbExecution( 'tbestellung', 'dBezahltDatum = now(), cKommentar = CONCAT(cKommentar, "' . $txn_message . '"), cStatus=' . constant( $oPlugin->oPluginEinstellungAssoc_arr[$order->payment_type.'_set_order_status'] ), 'cBestellNr = "' . $orderId . '"' ); // Updates the value into the database
			}
		} else { // Condition to check communication failure for the payment error
			$txn_message .=  $jtlPaymentClass->getResponseText( $callbackRequestParams );

			NovalnetGateway::performDbExecution( 'tbestellung', 'cKommentar = CONCAT(cKommentar, "' . $txn_message . '"), cStatus=' . constant( $oPlugin->oPluginEinstellungAssoc_arr['cancel_order_status'] ), 'cBestellNr = "' . $orderId . '"' ); // Updates the value into the database
		}
		
		$jtlPaymentClass->insertOrderIntoDBForFailure( array( 'tid' => $callbackRequestParams['shop_tid'], 'inputval3' => $order->payment_type, 'email' => $callbackRequestParams['email'] ,'status' => $callbackRequestParams['tid_status'] ), $order ); // Insert the order details into Novalnet table 

		if ( trim( $oPlugin->oPluginEinstellungAssoc_arr['vendorid'] ) != $callbackRequestParams['vendor_id'] ) {
			$insertAffiliate = new stdClass();
			$insertAffiliate->nAffId      = $callbackRequestParams['vendor_id'];
			$insertAffiliate->cCustomerId = $order->kKunde;
			$insertAffiliate->nAffOrderNo = $order->cBestellNr;
			Shop::DB()->insertRow( 'xplugin_novalnetag_taff_user_detail', $insertAffiliate );
		}
		
		$jtlPaymentmethod->sendMail( $order->kBestellung, MAILTEMPLATE_BESTELLUNG_AKTUALISIERT );

		$callbackScriptText = html_entity_decode( $order->cZahlungsartName ) . ' payment status updated';

		$this->callbackFinalProcess( $callbackScriptText, $order, false, false ); // Completes the callback execution
	}

	/**
	 * Update affiliate process details into the Novalnet table for reference
	 *
	 * @param array $datas
	 * @return bool
	 */
	public function updateAffiliateDatas( $datas )
	{
		$insertAffiliate = new stdClass();
		$insertAffiliate->nVendorId	      = $datas['vendor_id'];
		$insertAffiliate->cVendorAuthcode = $datas['vendor_authcode'];
		$insertAffiliate->nProductId 	  = $datas['product_id'];
		$insertAffiliate->cProductUrl 	  = $datas['product_url'];
		$insertAffiliate->dActivationDate = $datas['activation_date'];
		$insertAffiliate->nAffId 		  = $datas['aff_id'];
		$insertAffiliate->cAffAuthcode    = $datas['aff_authcode'];
		$insertAffiliate->cAffAccesskey   = $datas['aff_accesskey'];

		Shop::DB()->insertRow( 'xplugin_novalnetag_taffiliate_account_detail' , $insertAffiliate );
		
		return true;
	}

	/**
	 * Performs final callback process
	 *
	 * @param array  $callbackScriptText
	 * @param array  $orderReference
	 * @param bool   $recurring
	 * @param bool   $updateComments
	 * @param string $greaterAmount
	 * @return none
	 */
	public function callbackFinalProcess( $callbackScriptText, $orderReference, $recurring = false, $updateComments = true, $greaterAmount = '' )
	{
		//update callback comments in Novalnet table
		if ( $updateComments ) {
			NovalnetGateway::performDbExecution( 'tbestellung', 'cKommentar = CONCAT(cKommentar, "' . $callbackScriptText . '")', 'cBestellNr = "' . $orderReference->cBestellNr . '"' ); // Updates the value into the database
		}

		$callbackScriptText = $callbackScriptText . $greaterAmount;

		//Send notification mail to Merchant
		$this->sendMailNotification( array(
			'comments' => $callbackScriptText,
			'orderNo'  => $orderReference->cBestellNr
		) );

		// Log callback process (for all types of payments default)
		$this->logCallbackProcess( $orderReference, $recurring );
		$this->debugError( $callbackScriptText );
	}

	/**
	 * To log callback process into the callback table
	 *
	 * @param  object $orderReference
	 * @param  bool   $recurring
	 * @return bool
	 */
	public function logCallbackProcess( $orderReference, $recurring )
	{
		$orderAmount = Shop::DB()->query( 'SELECT fGesamtsumme FROM tbestellung WHERE cBestellNr = "' . $orderReference->cBestellNr . '"', 1);
		
		$insertCallback = new stdClass();
		$insertCallback->dDatum		  	 = date('Y-m-d H:i:s');
		$insertCallback->cZahlungsart 	 = $this->arycaptureparams['payment_type'];
		$insertCallback->nReferenzTid 	 = $this->arycaptureparams['tid'];
		$insertCallback->nCallbackTid 	 = $this->arycaptureparams['shop_tid'];
		$insertCallback->nCallbackAmount = ( $recurring || $this->arycaptureparams['tid_status'] == 90 ) ? 0 : ( ( $orderReference->cZahlungsartName == 'novalnet_paypal' ) ? ( $orderAmount->fGesamtsumme * 100 ) : $this->arycaptureparams['amount'] );
		$insertCallback->cWaehrung 		 = $orderReference->kWaehrung;
		$insertCallback->cBestellnummer  = $orderReference->cBestellNr;
		Shop::DB()->insertRow( 'xplugin_novalnetag_tcallback', $insertCallback );

		return true;
	}
}
