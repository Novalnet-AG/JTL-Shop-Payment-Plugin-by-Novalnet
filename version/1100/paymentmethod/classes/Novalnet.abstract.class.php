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
 * Script: Novalnet.abstract.class.php
 *
 */
require_once( 'Novalnet.helper.class.php' );

/**
 * Class NovalnetGateway
 */
class NovalnetGateway
{

	/**
     * @var Plugin
     */
	public $oPlugin;

	/**
     * @var null|instance
     */
	static $_instance = null;

	/**
     * @var null|NovalnetHelper
     */
	public $helper = null;

	/**
     * @var string|null
     */
	public $tariffValues;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		// Creates instance for the NovalnetHelper class
		$this->helper = new NovalnetHelper();
		
		// Retrieves Novalnet plugin object
		$this->oPlugin = nnGetPluginObject();
		
		// Assigns error message to the shop's variable $hinweis
		$this->assignPaymentError();
		
		// Gets tariff values to extract tariff id and tariff type 
		$this->tariffValues = $this->helper->getConfigurationParams( 'tariffid' );
	}

	/**
	 * Checks & assigns manual limit
	 *
	 * @param double $amount
	 * @param string $payment
	 * @return bool
	 */
	public function doManualLimitCheck( $amount, $payment )
	{		
		return ( nnIsDigits( $this->helper->getConfigurationParams( 'manual_check_limit' ) ) && $amount >= $this->helper->getConfigurationParams( 'manual_check_limit' ) && ( in_array( $payment, array( 'novalnet_cc', 'novalnet_sepa', 'novalnet_invoice' ) ) ) );
	}

	/**
	 * Build payment parameters to server
	 *
	 * @param object $order	 
	 * @param string $payment
	 * @return array
	 */
	public function generatePaymentParams( $order, $payment )
	{
		$affDetails = $this->helper->getAffiliateDetails(); // Get affiliate details for the current user

		$orderAmount = gibPreisString( $order->fGesamtsumme ) * 100; // Form order amount in cents with conversion using shop function
		
		$amount = ( $this->helper->getConfigurationParams( 'extensive_option', $payment ) == '2' && $this->tariffValues[1] == 2 ) ? 0 : $orderAmount; // Assigns amount based on zero-amount enable option
		
		/******** Basic parameters *****/
		
		$paymentRequestParameters = array(
			'vendor'		   => empty( $affDetails->vendorid ) ? $this->helper->getConfigurationParams( 'vendorid' ) : $affDetails->vendorid,
			'auth_code' 	   => empty( $affDetails->cAffAuthcode ) ? $this->helper->getConfigurationParams( 'authcode' ) : $affDetails->cAffAuthcode,
			'product'   	   => $this->helper->getConfigurationParams( 'productid' ),
			'tariff'    	   => $this->tariffValues[0],
			'test_mode' 	   => $this->helper->getConfigurationParams( 'testmode', $payment ) ? 1 : 0,
			'amount'		   => $amount,
			'currency'	       => $order->Waehrung->cISO,

		/******** Customer parameters *****/
		
			'remote_ip'	       => getRealIp(),
			'first_name'       => !empty( $_SESSION['Kunde']->cVorname ) ? $_SESSION['Kunde']->cVorname : $_SESSION['Kunde']->cNachname,
			'last_name'        => !empty( $_SESSION['Kunde']->cNachname ) ? $_SESSION['Kunde']->cNachname : $_SESSION['Kunde']->cVorname,
			'gender'      	   => 'u',
			'email'       	   => !empty( $_SESSION[$payment]['nn_mail'] ) ? trim( $_SESSION[$payment]['nn_mail'] ) : trim( $_SESSION['Kunde']->cMail ),
			'street'      	   => $_SESSION['Kunde']->cStrasse . ',' . $_SESSION['Kunde']->cHausnummer,
			'search_in_street' => 1,
			'city'             => $_SESSION['Kunde']->cOrt,
			'zip'  			   => $_SESSION['Kunde']->cPLZ,
			'language'         => nnGetShopLanguage(), // Returns the current shop language
			'lang'             => nnGetShopLanguage(),
			'country_code'     => $_SESSION['Kunde']->cLand,
			'country'          => $_SESSION['Kunde']->cLand,
			'tel'              => !empty( $_SESSION[$payment]['nn_tel_number'] ) ? $_SESSION[$payment]['nn_tel_number'] : $_SESSION['Kunde']->cTel,
			'mobile'           => !empty( $_SESSION[$payment]['nn_mob_number'] ) ? $_SESSION[$payment]['nn_mob_number'] : $_SESSION['Kunde']->cTel,
			'customer_no'      => !empty( $_SESSION['Kunde']->kKunde ) ? $_SESSION['Kunde']->kKunde : 'guest',

		/******** System parameters *****/
			
			'system_name'      => 'jtlshop',
			'system_version'   => nnGetFormattedVersion( Shop::getVersion() ) . '_NN_11.0.0',
			'system_url'       => Shop::getURL(),
			'system_ip'        => nnGetServerAddr(), // Returns the IP address of the server
			'notify_url'       => $this->helper->getConfigurationParams( 'callback_notify_url' )
		);

		if ( !empty( $_SESSION['Kunde']->cFirma ) ) { // Check if company field is given
			$paymentRequestParameters['company'] = $_SESSION['Kunde']->cFirma;
		}
		
		/******** Additional parameters *****/

		if ( $this->doManualLimitCheck( $orderAmount, $payment ) ) { // Manual limit check for the order
			$paymentRequestParameters['on_hold'] = 1;
		}
		
		if ( nnIsDigits( $this->helper->getConfigurationParams( 'referrerid' ) ) ) { // Check if the Referrer ID is an integer 
			$paymentRequestParameters['referrer_id'] = $this->helper->getConfigurationParams( 'referrerid' );
		}

		if ( $this->helper->getConfigurationParams( 'reference1', $payment ) != '' ) { // Check if the Referrer ID is an integer 
			$paymentRequestParameters['input1']		= 'reference1';
			$paymentRequestParameters['inputval1']	= trim( strip_tags( $this->helper->getConfigurationParams('reference1', $payment ) ) );
		}

		if ( $this->helper->getConfigurationParams( 'reference2', $payment ) != '' ) { // Check if the transaction reference 1 is valid
			$paymentRequestParameters['input2']		= 'reference2';
			$paymentRequestParameters['inputval2']	= trim( strip_tags( $this->helper->getConfigurationParams('reference2', $payment ) ) );
		}

		if ( $this->helper->getConfigurationParams( 'tariff_period' ) != '' ) { // Check if the transaction reference 2 is valid
			$paymentRequestParameters['tariff_period'] = $this->helper->getConfigurationParams( 'tariff_period' );
		}

		if ( nnIsDigits( $this->helper->getConfigurationParams( 'tariff_period2_amount' ) ) && $this->helper->getConfigurationParams( 'tariff_period2' ) != '' ) { // Check if the tariff period2 amount is valid
			$paymentRequestParameters['tariff_period2'] = $this->helper->getConfigurationParams( 'tariff_period2' );
			$paymentRequestParameters['tariff_period2_amount'] = $this->helper->getConfigurationParams('tariff_period2_amount' );
		}

		if ( $_SESSION['Zahlungsart']->nWaehrendBestellung == 0 )
			$paymentRequestParameters['order_no'] = $order->cBestellNr;

		if ( $this->canEnableFraudModule( $payment ) && empty( $_SESSION[$payment]['one_click_shopping'] ) ) { // Check if fraud module is active
			switch ( $this->helper->getConfigurationParams( 'pin_by_callback', $payment ) ) {
				case '1':
					$paymentRequestParameters['pin_by_callback']  = 1;
					break;
				case '2':
					$paymentRequestParameters['pin_by_sms'] = 1;
					break;
				case '3':
					$paymentRequestParameters['reply_email_check'] = 1;
					break;	
			}
		}
		return $paymentRequestParameters;
	}
	
	/**
	 * Make second call to server for fraud module 
	 *
	 * @param array $order
	 * @param string $payment
	 * @return none
	 */
	public function doSecondCall( $order, $payment )
	{
		$this->orderAmountCheck( $order->fGesamtsumme, $payment ); // Check when order amount has been changed after first payment call
		
		$aryResponse = $this->getPinStatus( $_SESSION[$payment]['tid'], ( !empty( $_SESSION['nn_fraudmodule']['nn_forgot_pin'] ) ) ? 'TRANSMIT_PIN_AGAIN' : ( $this->helper->getConfigurationParams( 'pin_by_callback', $payment ) == '3' ? 'REPLY_EMAIL_STATUS' : 'PIN_STATUS' ) ); // Do transaction XML call for fraud module
		
		if ( $aryResponse['status'] == 100 ) { // Return on successful response
			return true;
		} else { // Perform error response operations
			if ( $aryResponse['status'] == '0529006' ) {
				$_SESSION[$payment . '_invalid']    = TRUE;
				$_SESSION[$payment . '_time_limit'] = time()+(30*60);
				$_SESSION['nn_mail'] = $_SESSION['Kunde']->cMail;
				unset( $_SESSION[$payment]['tid'] );
			} elseif ( $aryResponse['status'] == '0529008' ){
				unset( $_SESSION[$payment]['tid'] );
			}

			$_SESSION['nn_error'] = utf8_decode( !empty( $aryResponse['status_message'] ) ? $aryResponse['status_message'] : ( !empty( $aryResponse['pin_status']['status_message'] ) ? $aryResponse['pin_status']['status_message'] : '' ) );
			
			$this->helper->redirectOnError(); // Redirects to the error page
		}
	}

	/**
	 * Storing response parameters when fraud module enabled
	 *
	 * @param array $response
	 * @param double $amountToStore
	 * @return none
	 */
	public function storeFraudModuleValues( $response, $amountToStore )
	{
		$_SESSION[$response['inputval3']]['test_mode'] = !empty( $response['test_mode'] ) ? $response['test_mode'] : '';
		$_SESSION[$response['inputval3']]['tid'] = $response['tid'];
		$_SESSION[$response['inputval3']]['status'] = $response['tid_status'];
		$_SESSION['nn_payment'] = $response['inputval3'];
		$_SESSION['nn_amount'] = $amountToStore;
		$_SESSION[$response['inputval3']]['currency'] = $response['currency'];

		if ( $response['inputval3'] == 'novalnet_invoice' ) { // Check the fraud module is an Novalnet invoice payment	
			$_SESSION[$response['inputval3']]['due_date'] 		   = $response['due_date'];
			$_SESSION[$response['inputval3']]['invoice_iban'] 	   = $response['invoice_iban'];
			$_SESSION[$response['inputval3']]['invoice_bic'] 	   = $response['invoice_bic'];
			$_SESSION[$response['inputval3']]['invoice_bankname']  = $response['invoice_bankname'];
			$_SESSION[$response['inputval3']]['invoice_bankplace'] = $response['invoice_bankplace'];
			$_SESSION[$response['inputval3']]['invoice_account']   = $response['invoice_account'];
			$_SESSION[$response['inputval3']]['invoice_bankcode']  = $response['invoice_bankcode'];
		}
	}

	/**
	 * Compare the hash generated for redirection payments
	 *
	 * @param array $response
	 * @param array $order
	 * @return none
	 */
	public function hashCheckForRedirects( $response, $order )
	{
		if ( !$this->helper->checkResponseHash( $response ) )
		{ // Condition to check whether the payment is redirect
		
			$_SESSION['nn_error'] = html_entity_decode( $this->oPlugin->oPluginSprachvariableAssoc_arr['__NN_hash_error'] );
				
			if ( empty( $_SESSION['nn_during_order'] ) )
			{
				header( 'Location:' . Shop::getURL() . '/bestellvorgang.php?editZahlungsart=1' );
				exit();
			} else {
				$this->helper->redirectOnError( $response ); // Redirects to the error page
			}
		}
	}

	/**
	 * To display additional form fields for fraud prevention setup
	 *
	 * @param string $payment
	 * @return none
	 */
	public function displayFraudCheck( $payment )
	{
		$this->helper->novalnetSessionUnset( $payment ); // Unsets the other Novalnet payment sessions

		if ( $this->helper->getConfigurationParams( 'pin_by_callback', $payment ) == '0' || !$this->canEnableFraudModule( $payment ) ) { 
			return true;
		} else {
			if ( isset ( $_SESSION[$payment]['tid'] ) ) { // If fraud module enabled				
				if ( $this->helper->getConfigurationParams( 'pin_by_callback', $payment ) == '3' )
					return true;
				else
					Shop::Smarty()->assign( 'pin_enabled', $payment );
			} else {
				if ( $this->helper->getConfigurationParams( 'pin_by_callback', $payment ) == '1' ) // If pin by callback enabled				
					Shop::Smarty()->assign( 'pin_by_callback', $payment );
				elseif ( $this->helper->getConfigurationParams( 'pin_by_callback', $payment ) == '2' ) // If pin by sms enabled
					Shop::Smarty()->assign( 'pin_by_sms', $payment );
				else // If reply by email enabled
					Shop::Smarty()->assign( 'reply_by_email', $payment );
			}
		}
	}
	
	/**
	 * Make CURL payment request to Novalnet server
	 *
	 * @param string $payment
	 * @param double|null $orderAmount
	 * @return none
	 */
	public function performServerCall( $payment, $orderAmount = '' )
	{
		$transactionResponse = http_get_contents( 'https://payport.novalnet.de/paygate.jsp', $this->getGatewayTimeout(), $_SESSION['nn_request'] ); // Core function - CURL request to server
		parse_str( $transactionResponse, $response );

		$_SESSION['nn_success'] = $response; // Assigns the response to session when handling notify URL

		if ( $response['status'] == 100 ) { // Handles server response for success

			if ( $this->helper->getConfigurationParams( 'pin_by_callback', $payment ) != '0' && $this->canEnableFraudModule( $payment ) && !empty( $_SESSION[$payment]['is_fraudcheck'] ) ) { // Condition to check whether the payment can be completed through fraud prevention setup

				$this->storeFraudModuleValues( $response, $this->helper->getConfigurationParams( 'extensive_option', $payment ) == '2' ? $orderAmount : $response['amount'] ); // Storing response parameters when fraud module enabled
				
				$_SESSION['nn_error'] = ( $this->helper->getConfigurationParams( 'pin_by_callback', $payment ) == '3' ) ? $this->oPlugin->oPluginSprachvariableAssoc_arr['__NN_reply_by_mail_message'] : ( $this->helper->getConfigurationParams( 'pin_by_callback', $payment ) == '2' ? $this->oPlugin->oPluginSprachvariableAssoc_arr['__NN_sms_pin_message'] : $this->oPlugin->oPluginSprachvariableAssoc_arr['__NN_callback_pin_message'] );

				header( 'Location:' . Shop::getURL() . '/bestellvorgang.php?editZahlungsart=1');
				exit();
			}
		} else { // Handles server response for error

			$_SESSION['nn_error'] = $response['status_desc'];
			
			$this->helper->redirectOnError( $response ); // Redirects to the error page
		}
	}

	/**
	 * Process while handling handle_notification URL
	 *
	 * @param array   $order
	 * @param string  $sessionHash
	 * @param integer $paymentKey
	 * @return none
	 */
	public function handleViaNotification( $order, $sessionHash, $paymentKey, $response = array() )
	{
		if ( empty( $response ) ) {
			$response = $_SESSION['nn_success'];
		}

		if ( !empty( $_SESSION['nn_during_order'] ) ) {
			
			if ( in_array( $response['status'], array( 90, 100 ) ) ) { // On successful payment server response

				$paymentId = $this->getPaymentMethod( $order->kZahlungsart, false ); // Retrieve payment method mapped for the plugin to log the transaction response
				
				$jtlPaymentmethod = new PaymentMethod( 'kPlugin_' . $this->oPlugin->kPlugin . '_' . $paymentId ); // Instance of class PaymentMethod
				
				if ( !empty( $response['key'] ) ) {
					$this->hashCheckForRedirects( $response, $order ); // Compare the hash generated for redirection payments
				}

				$comments = $this->formTransactionComments( $response, $order ); // Form transaction comments for the current order

				NovalnetGateway::performDbExecution( 'tbestellung', 'cKommentar = CONCAT(cKommentar, "' . $comments . '")', 'kBestellung =' . $order->kBestellung ); // Updates the value into the database

				$jtlPaymentmethod->sendMail( $order->kBestellung, 	MAILTEMPLATE_BESTELLUNG_AKTUALISIERT );	
			} else {

				$_SESSION['nn_error'] = $this->getResponseText( $response );
				
				$this->insertOrderIntoDBForFailure( $response, $order ); // Logs the order details in Novalnet tables for failure
						
				$this->helper->redirectOnError( $response ); // Redirects to the error page
			}
					
		} else {
			$this->postBackCall( $response, $order->cBestellNr, $paymentKey, $order->cKommentar ); // Post back acknowledgement call to map the order into Novalnet server
		}

		$this->insertOrderIntoDB( $response, $order->kBestellung, $paymentKey ); // Logs the order details in Novalnet tables for success		
	
		$this->helper->novalnetSessionCleanUp( !empty( $response['inputval3'] ) ? $response['inputval3'] : $_SESSION['nn_payment'] ); // Unset the entire novalnet session on order completion

		header( 'Location: ' . Shop::getURL() . '/bestellabschluss.php?i=' . $sessionHash );
		exit();		
	}

	/**
	 * Finalize the order
	 *
	 * @param array  $order
	 * @param array  $response
	 * @return bool
	 */
    public function verifyNotification( $order, $response = array() )
    {
		if ( empty( $response ) ) {
			$response = $_SESSION['nn_success'];
		}

		if ( in_array( $response['status'], array( 90, 100 ) ) ) { // On successful payment server response
			if ( !empty( $response['key'] ) ) {
				$this->hashCheckForRedirects( $response, $order ); // Compare the hash generated for redirection payments
			}
				
			$_POST['kommentar'] = $this->formTransactionComments( $response, $order ); // Form transaction comments for the current order	
			unset( $_SESSION['kommentar'] );			

			return true;
		} else { // Assign error test on payment server response

			$this->helper->novalnetSessionCleanUp( !empty( $response['inputval3'] ) ? $response['inputval3'] : $_SESSION['nn_payment'] ); // Unset the entire novalnet session on error
			
			$_SESSION['nn_error'] = $this->getResponseText( $response );

			header( 'Location: ' . Shop::getURL() . '/bestellvorgang.php?editZahlungsart=1' );
			exit();
		}
    }

	/**
	 * Build the Novalnet order comments
	 *
	 * @param array $response
	 * @param array $order
	 * @return string
	 */
	public function formTransactionComments( $response, $order )
	{
		$transactionComments = !empty( $_SESSION['kommentar'] ) ? $_SESSION['kommentar'] . PHP_EOL . PHP_EOL . $order->cZahlungsartName . PHP_EOL : $order->cZahlungsartName . PHP_EOL;

		if ( isset( $_SESSION[$response['inputval3']]['tid'] ) ) {
			$response['test_mode']    	= $_SESSION[$response['inputval3']]['test_mode'];
			$response['tid']          	= $_SESSION[$response['inputval3']]['tid'];
			$response['amount']  		= $_SESSION['nn_amount'];
		}
		
		if ( !nnIsDigits( $response['test_mode'] ) ) {
			$response['test_mode'] = $this->helper->generateDecode( $response['test_mode'] );
		}

		if ( !empty( $response['test_mode'] ) || $this->helper->getConfigurationParams( 'testmode', $response['inputval3'] ) != '' ) { // Condition to retrieve the testmode for the payment
			$transactionComments .= $this->oPlugin->oPluginSprachvariableAssoc_arr['__NN_test_order'] . PHP_EOL;
		}

		$transactionComments .= $this->oPlugin->oPluginSprachvariableAssoc_arr['__NN_tid_label'] . $response['tid'] . PHP_EOL;
		$transactionComments .= $response['status'] != 100 ? $this->getResponseText( $response ) . PHP_EOL : '';

		if ( in_array( $response['inputval3'], array( 'novalnet_invoice', 'novalnet_prepayment' ) ) ) {
			$invoicePaymentsComments = $this->formInvoicePrepaymentComments( $response, $order->Waehrung->cISO );
			$transactionComments .= $invoicePaymentsComments;
		}
				
		return $transactionComments;
	}

	/**
	 * Form invoice & prepayment payments comments
	 *
	 * @param array  $datas
	 * @param string $currency
	 * @param bool   $updateAmount
	 * @return string
	 */
	public function formInvoicePrepaymentComments( $datas, $currency, $updateAmount = false )
	{
		$datas = array_map( 'utf8_decode', $datas );

		$orderNo = !empty( $datas['order_no'] ) ? $datas['order_no'] : 'NN_ORDER';
		$duedate = new DateTime( $datas['due_date'] );
		$transComments  = PHP_EOL . $this->oPlugin->oPluginSprachvariableAssoc_arr['__NN_invoice_payments_comments'] . PHP_EOL;
		$transComments .= $this->oPlugin->oPluginSprachvariableAssoc_arr['__NN_invoice_duedate'] . $duedate->format('d.m.Y') . PHP_EOL;
		$transComments .= $this->oPlugin->oPluginSprachvariableAssoc_arr['__NN_invoice_payments_holder'] . PHP_EOL;
		$transComments .= 'IBAN: ' . $datas['invoice_iban'] . PHP_EOL;
		$transComments .= 'BIC:  ' . $datas['invoice_bic'] . PHP_EOL;
		$transComments .= 'Bank: ' . $datas['invoice_bankname'] . ' ' . $datas['invoice_bankplace'] . PHP_EOL;
		$transComments .= $this->oPlugin->oPluginSprachvariableAssoc_arr['__NN_invoice_payments_amount'] . number_format( $datas['amount'], 2, ',', '' ) . ' ' . $currency . PHP_EOL;
		$referenceParams = $updateAmount ? unserialize( $datas['referenceValues'] ) : $this->helper->getInvoicePaymentsReferences( $datas['inputval3'] );
		$refCount = array_count_values( $referenceParams );
		$referenceSuffix = array( 'BNR-' . $this->helper->getConfigurationParams( 'productid' ) . '-' . $orderNo, 'TID ' . $datas['tid'], $this->oPlugin->oPluginSprachvariableAssoc_arr['__NN_order_number_text'] . $orderNo );
		$i = 1;

		$transComments .= ( ( $refCount['on'] > 1 ) ? $this->oPlugin->oPluginSprachvariableAssoc_arr['__NN_invoice_payments_multiple_reference_text'] : $this->oPlugin->oPluginSprachvariableAssoc_arr['__NN_invoice_payments_single_reference_text'] ) . PHP_EOL;

		foreach ( $referenceParams as $key => $val ) {
			if ( !empty( $val ) ) {
				$suffix = ( $_SESSION['cISOSprache'] == 'ger' && $refCount['on'] > 1 ) ? $i . '. ' : ( $refCount['on'] > 1 ? $i : '' );
				$transComments .= sprintf( $this->oPlugin->oPluginSprachvariableAssoc_arr['__NN_invoice_payments_reference'], $suffix ) . ': ' . $referenceSuffix[$key] . PHP_EOL;
				$i+=1;
			}
		}
		
		return $transComments;
	}

	/* Make XML call to server to perform the fraudmodule operation
	 *
	 * @param integer $tidVal
	 * @param string  $requestType
	 * @return array
	 */
	public function getPinStatus( $tidVal, $requestType )
	{
		$vendorId = $this->helper->getConfigurationParams( 'vendorid' );
		$authcode = $this->helper->getConfigurationParams( 'authcode' );
		
		if ( !empty( $_SESSION['nn_aff_id'] ) ) {
			$affDetails = $this->helper->getAffiliateDetails(); // Get affiliate details for the current user
			$vendorId = $affDetails->vendorid;
			$authcode = $affDetails->cAffAuthcode;
		}

		$fraudModuleParams  = '<?xml version="1.0" encoding="UTF-8"?><nnxml><info_request><vendor_id>' . $vendorId . '</vendor_id>';
        $fraudModuleParams .= '<vendor_authcode>' . $authcode . '</vendor_authcode>';
        $fraudModuleParams .= '<request_type>' . $requestType . '</request_type>';
        $fraudModuleParams .= '<tid>' . $tidVal . '</tid>';
		if( $requestType == 'PIN_STATUS' )
			$fraudModuleParams .= '<pin>' . $_SESSION['nn_fraudmodule']['nn_pin'] . '</pin>';
		$fraudModuleParams .= '<lang>' . nnGetShopLanguage() . '</lang>';
        $fraudModuleParams .='</info_request></nnxml>';
       
        $transactionResponse = http_get_contents( 'https://payport.novalnet.de/nn_infoport.xml', $this->getGatewayTimeout(), $fraudModuleParams );
		$response = simplexml_load_string( $transactionResponse );
		$response = json_decode( json_encode( $response ), true );

		return $response;
	}
		
	/**
	 * Perform postback call to novalnet server
	 *
	 * @param array   $response
	 * @param string  $orderNo	 
	 * @param integer $paymentKey	 
	 * @param string  $orderComments	 
	 * @return none
	 */
	public function postBackCall( $response, $orderNo, $paymentKey, $orderComments )
	{	
		$postData = array(
			'vendor'	=> $this->helper->getConfigurationParams( 'vendorid' ),
			'product'   => $this->helper->getConfigurationParams( 'productid' ),
			'tariff'    => $this->tariffValues[0],
			'auth_code' => $this->helper->getConfigurationParams( 'authcode' ),
			'key'       => $paymentKey,
			'status'    => 100,
			'tid'       => !empty( $response['tid'] ) ? $response['tid'] : $_SESSION[$response['inputval3']]['tid'],
			'order_no'  => $orderNo
		);

		if ( $paymentKey == 27 ) {
			$postData['invoice_ref'] = 'BNR-' . $postData['product']  . '-' . $orderNo;
			$orderComments = str_replace( 'NN_ORDER', $orderNo, $orderComments );
			self::performDbExecution( 'tbestellung', 'cKommentar = "' . $orderComments . '"' , 'cBestellNr ="' . $orderNo . '"' );
		}

		http_get_contents( 'https://payport.novalnet.de/paygate.jsp', $this->getGatewayTimeout(), $postData );
	}

	/**
	 * To insert the order details into novalnet tables
	 *
	 * @param array   $response
	 * @param integer $orderValue
	 * @param string  $paymentKey
	 * @return none
	 */
	public function insertOrderIntoDB( $response, $orderValue, $paymentKey )
	{
		$order = new Bestellung( $orderValue ); // Loads the order object from shop 

		$tid = !empty( $response['tid'] ) ? $response['tid'] : $_SESSION[$response['inputval3']]['tid'];

		$vendorId = $this->helper->getConfigurationParams( 'vendorid' );
		$authcode = $this->helper->getConfigurationParams( 'authcode' );

		if ( !empty( $_SESSION['nn_aff_id'] ) ) {
			$affDetails = $this->helper->getAffiliateDetails(); // Get affiliate details for the current user
			$vendorId = $affDetails->vendorid;
			$authcode = $affDetails->cAffAuthcode;
		}
		
		$insertOrder = new stdClass();
		$insertOrder->cNnorderid        = $order->cBestellNr;
		$insertOrder->cKonfigurations   = serialize( array( 'vendor' => $vendorId, 'auth_code' => $authcode, 'product' => $this->helper->getConfigurationParams( 'productid' ), 'tariff' => $this->tariffValues[0], 'key' => $paymentKey ) );
		$insertOrder->nNntid 		    = $tid;
		$insertOrder->cZahlungsmethode  = !empty( $response['inputval3'] ) ? $response['inputval3'] : $response['payment'];
		$insertOrder->cMail			    = $_SESSION['Kunde']->cMail;
		$insertOrder->nStatuswert		= !empty( $_SESSION[$response['inputval3']]['status'] ) ? $_SESSION[$response['inputval3']]['status'] : $response['tid_status'];
		$insertOrder->bOnetimeshopping  = !empty( $_SESSION[$response['inputval3']]['one_click_shopping'] ) ? $_SESSION[$response['inputval3']]['one_click_shopping'] : 0;
		$insertOrder->cZeroBookingParams= !empty( $_SESSION['nn_booking'] ) ? serialize( $_SESSION['nn_booking'] ) : '';
		$insertOrder->nBetrag 		    = !empty( $response['key'] ) ? $this->helper->generateDecode( $response['amount'] ) : ( ( !empty( $response['amount'] ) ? $response['amount'] : $_SESSION['nn_amount'] ) * 100 );
		$insertOrder->cSepaHash        = ( !empty( $_SESSION['novalnet_sepa']['nn_payment_hash'] ) && $_SESSION['Kunde']->nRegistriert != '0') ? $_SESSION['novalnet_sepa']['nn_payment_hash'] : '';
		$insertOrder->cMaskedDetails    = in_array( $paymentKey, array( 6, 37 ) ) ? $this->getMaskedPatternToStore( $response, $paymentKey ) : '';

        Shop::DB()->insertRow( 'xplugin_novalnetag_tnovalnet_status', $insertOrder );

		if ( $paymentKey != 27 && $response['status'] == 100 ) { // If the payment server response is successful
			
			$insertCallback = new stdClass();
			$insertCallback->cBestellnummer  = $insertOrder->cNnorderid;
			$insertCallback->dDatum		  	 = date('Y-m-d H:i:s');
			$insertCallback->cZahlungsart 	 = $order->cZahlungsartName;
			$insertCallback->nReferenzTid 	 = $tid;
			$insertCallback->nCallbackAmount = $insertOrder->nBetrag;
			$insertCallback->cWaehrung 		 = isset( $response['currency'] ) ? $response['currency'] : $_SESSION[$response['inputval3']]['currency'];
				
			Shop::DB()->insertRow( 'xplugin_novalnetag_tcallback', $insertCallback );
		}

		if ( !empty( $response['subs_id'] ) ) { // If the order is a subscription order
			
			$insertSubscription = new stdClass();
			$insertSubscription->cBestellnummer = $insertOrder->cNnorderid;
			$insertSubscription->nSubsId 	    = $response['subs_id'];
			$insertSubscription->nTid 		    = $tid;
			$insertSubscription->dSignupDate    = date('Y-m-d H:i:s');
			
			Shop::DB()->insertRow( 'xplugin_novalnetag_tsubscription_details', $insertSubscription );
		}
		
		if ( !empty( $_SESSION['nn_aff_id'] ) ) { // If the order is an affiliate order
			
			$insertAffiliate = new stdClass();
			$insertAffiliate->nAffId      = $vendorId;
			$insertAffiliate->cCustomerId = $order->kKunde;
			$insertAffiliate->nAffOrderNo = $insertOrder->cNnorderid;
			
			Shop::DB()->insertRow( 'xplugin_novalnetag_taff_user_detail', $insertAffiliate );
		}
	}

	/**
	 * Assign and getback the masked pattern details
	 *
	 * @param array   $response
	 * @param integer  $paymentKey
	 * @return array
	 */
	function getMaskedPatternToStore( $response, $paymentKey )
	{
		if ( $paymentKey == 6 ) {
			return serialize ( array (
				'referenceOption1' => $response['cc_card_type'],
				'referenceOption2' => utf8_decode( $response['cc_holder'] ),
				'referenceOption3' => $response['cc_no'],
				'referenceOption4' => $response['cc_exp_month'] .'/'. $response['cc_exp_year']
			) );
		} else {
			return serialize ( array (
				'referenceOption1' => utf8_decode( $response['bankaccount_holder'] ),
				'referenceOption2' => $response['iban'],
				'referenceOption3' => $response['bic']
			) );
		}
	}

	/**
	 * To insert the order details into Novalnet table for failure
	 *
	 * @param array   $response
	 * @param object  $order
	 * @param string|null $paymentKey
	 * @return bool
	 */
	public function insertOrderIntoDBForFailure( $response, $order, $paymentKey = '' )
	{
		$vendorId = $this->helper->getConfigurationParams( 'vendorid' );
		$authcode = $this->helper->getConfigurationParams( 'authcode' );

		$affiliateDetails = $this->helper->getAffiliateDetails(); // Get affiliate details for the current user
		
		if ( !empty( $affiliateDetails ) ) {
			$vendorId = $affiliateDetails->vendorid;
			$authcode = $affiliateDetails->cAffAuthcode;
		}
		
		$insertOrder = new stdClass();
		$insertOrder->cNnorderid        = $order->cBestellNr;
		$insertOrder->cKonfigurations   = serialize( array( 'vendor' => $vendorId, 'auth_code' => $authcode, 'product' => $this->helper->getConfigurationParams( 'productid' ), 'tariff' => $this->tariffValues[0], 'key' => $paymentKey ) );
		$insertOrder->nNntid 			= $response['tid'];
		$insertOrder->cZahlungsmethode  = !empty( $response['inputval3'] ) ? $response['inputval3'] : $response['payment'];
		$insertOrder->cMail  			= $response['email'];
		$insertOrder->nStatuswert  		= $response['status'];
		$insertOrder->nBetrag 		 	= $order->fGesamtsumme;
		Shop::DB()->insertRow( 'xplugin_novalnetag_tnovalnet_status', $insertOrder );
		
		return true;
	}

	/**
	 * Sets and displays payment error
	 *
	 * @param none
	 * @return none
	 */
	public function assignPaymentError()
	{
		global $hinweis;

		$error = isset( $_SESSION['nn_error'] ) ? $_SESSION['nn_error'] : ( isset( $_SESSION['fraud_check_error'] ) ? $_SESSION['fraud_check_error'] : '' );

		if ( !empty( $error ) ) {
					
			$hinweis = $error;
			
			unset( $_SESSION['nn_error'] );
			unset( $_SESSION['fraud_check_error'] );
		}
	}

	/**
	 * Retrieves response status texts from response
	 *
	 * @param array $response
	 * @return string
	 */
	public function getResponseText( $response )
	{
		return ( utf8_decode( !empty( $response['status_desc'] ) ? $response['status_desc'] : ( !empty( $response['status_text'] ) ? $response['status_text'] : ( !empty( $response['status_message'] ) ? $response['status_message'] : '' ) ) ) );
	}
	
	/**
	 * Execute database update operation
	 *
	 * @param string $table
	 * @param string $fields
	 * @param string $value
	 * @return none
	 */
	public static function performDbExecution( $table, $fields, $value )
	{
		Shop::DB()->query( 'UPDATE ' . $table . ' SET ' . $fields . ' WHERE ' . $value, 10 );
	}

	/**
	 * Get gateway timeout limit
	 *
	 * @param none
	 * @return integer
	 */
	public function getGatewayTimeout()
	{
		return ( nnIsDigits( $this->helper->getConfigurationParams( 'gateway_timeout' ) ) ? $this->helper->getConfigurationParams( 'gateway_timeout' ) : 240 );
	}

	/**
	 * Get currency type for the current order
	 *
	 * @param string $orderNo
	 * @return string
	 */
	public static function getPaymentCurrency( $orderNo )
	{		
		$currency = Shop::DB()->query( 'SELECT twaehrung.cISO FROM twaehrung
	LEFT JOIN tbestellung ON twaehrung.kWaehrung = tbestellung.kWaehrung WHERE cBestellNr ="' . $orderNo . '"', 1);

		return $currency->cISO;
	}

	/**
	 * Retrieve payment methods stored in the shop
	 *
	 * @param integer $paymentNo
	 * @return array
	 */
	public function getPaymentMethod( $paymentNo, $returnValue = true )
	{
		$paymentMethodValue = Shop::DB()->query( 'SELECT cModulId FROM tzahlungsart WHERE kZahlungsart="' . $paymentNo . '"', 1);

		$paymentMethods = array( 'novalnetkaufaufrechnung' => 'novalnet_invoice', 'novalnetvorauskasse' => 'novalnet_prepayment', 'novalnetpaypal' => 'novalnet_paypal','novalnetkreditkarte' => 'novalnet_cc', utf8_decode( 'novalnetsofortüberweisung' ) => 'novalnet_banktransfer', 'novalnetideal' => 'novalnet_ideal', 'novalneteps' => 'novalnet_eps', 'novalnetlastschriftsepa' => 'novalnet_sepa', 'novalnetgiropay' => 'novalnet_giropay' );

		foreach ( $paymentMethods as $key => $value ) {
			if ( strpos( $paymentMethodValue->cModulId, $key ) )
				return $returnValue ? $value : $key;
		}		
	}

    /**
	 * Check to confirm guarantee payment option execution
	 *
	 * @param string $payment
	 * @param integer $orderAmount
	 * @return bool
	 */
	public function checkGuaranteedPaymentOption( $payment, $orderAmount )
	{
        if ( $this->helper->getConfigurationParams( 'guarantee', $payment ) != '' ) {

			if ( in_array( $_SESSION['Kunde']->cLand, array( 'DE','AT','CH' ) ) && $_SESSION['Waehrung']->cISO == 'EUR' && ( $orderAmount >= 2000 && $orderAmount <= 500000 ) ) { // Condition to check whether payment guarantee option can be processed		
				$_SESSION[$payment . '_guarantee'] = TRUE;				
				return true;
			} elseif ( $this->helper->getConfigurationParams( 'guarantee_force', $payment ) != '' ) {				
				if ( !empty( $_SESSION[$payment . '_guarantee'] ) )
					unset( $_SESSION[$payment . '_guarantee'] );			
				return true;
			} 
			if ( !empty( $_SESSION[$payment . '_guarantee'] ) )
				unset( $_SESSION[$payment . '_guarantee'] );		
			return false;
		}
		
		return true;
    }

	/******************** Validation process **************************/

	/**
	 * Check to validate basic parameters configured
	 * 
	 * @return bool
	 */
	public function isConfigInvalid()
	{		
		return ( $this->helper->getConfigurationParams( 'novalnet_public_key' ) == '' || empty( $this->tariffValues[0] ) );
	}
	
	/**
	 * Validates mandatory configuration parameters and customer parameters before requesting to the payment server
	 * 
	 * @param array $paymentRequestParameters
	 * @return bool
	 */
	public function preValidationCheckOnSubmission( $paymentRequestParameters )
	{		
		if ( $this->isConfigInvalid() || ( ( ( $this->helper->getConfigurationParams( 'tariff_period2' ) != '' ) && !nnIsDigits( $this->helper->getConfigurationParams( 'tariff_period2_amount' ) ) ) || ( nnIsDigits( $this->helper->getConfigurationParams( 'tariff_period2_amount' ) ) && ( $this->helper->getConfigurationParams('tariff_period2' ) == '' ) ) ) ) { // Validates the parameters configured and sets the payment error if not configured properly	
			$_SESSION['nn_error'] = utf8_encode( html_entity_decode ( $this->oPlugin->oPluginSprachvariableAssoc_arr['__NN_merchant_error'] ) );
			
		} elseif ( ( empty( $paymentRequestParameters['first_name'] ) && empty( $paymentRequestParameters['last_name'] ) ) || !valid_email( $paymentRequestParameters['email'] ) ) { // Validates the server customer mandatory parameters and sets the error if not configured properly
		
			$_SESSION['nn_error'] = $this->oPlugin->oPluginSprachvariableAssoc_arr['__NN_customer_details_error'];
			
		} elseif ( $paymentRequestParameters['key'] == 27 && !$this->helper->getConfigurationParams( $paymentRequestParameters['inputval3'] . '_payment_reference1' ) && !$this->helper->getConfigurationParams( $paymentRequestParameters['inputval3'] . '_payment_reference2' ) && !$this->helper->getConfigurationParams( $paymentRequestParameters['inputval3'] . '_payment_reference3' ) ) { // Validates the payment references for Invoice payments
			
			$_SESSION['nn_error'] = $this->oPlugin->oPluginSprachvariableAssoc_arr['__NN_invoice_payments_reference_error'];
		}

		if ( !empty( $_SESSION['nn_error'] ) ) {
			
			$this->helper->redirectOnError( $_SESSION['Zahlungsart']->nWaehrendBestellung == 0? array( 'order_no' => $paymentRequestParameters['order_no'] ) : '' ); // Redirects to the error page
		}
	}

	/**
	 * Validation for parameters on form payments
	 *
	 * @param bool $paymentSepa
	 * @return bool
	 */
	public function basicValidationOnhandleAdditional( $paymentSepa = false )
	{
		if ( $paymentSepa && nnIsDigits( $this->helper->getConfigurationParams( 'sepa_due_date' ) ) && ( $this->helper->getConfigurationParams( 'sepa_due_date' ) < 7 ) ) { // Condition to check whether the SEPA due date is valid 
			$_SESSION['nn_error'] = $this->oPlugin->oPluginSprachvariableAssoc_arr['__NN_sepa_duedate_error'];
		} elseif ( $this->helper->getConfigurationParams( 'manual_check_limit' ) && !nnIsDigits( $this->helper->getConfigurationParams( 'manual_check_limit' ) ) ) { // Condition to check manual check limit
			$_SESSION['nn_error'] = $this->oPlugin->oPluginSprachvariableAssoc_arr['__NN_merchant_error'];
		} elseif ( !empty( $_POST['nn_dob'] ) ) { // Condition to check if the birthdate is eligible for guarantee payment 
			$dateDifference = date_diff( date_create( date( 'Y-m-d' ) ), date_create( date( 'Y-m-d', strtotime( $_POST['nn_dob'] ) ) ) );
			
			if ( $dateDifference->y < 18 ) {				
				$_SESSION['nn_error'] = $this->oPlugin->oPluginSprachvariableAssoc_arr['__NN_age_limit_error'];
			}
		}
		
		if ( !empty ( $_SESSION['nn_error'] ) ) {
			Shop::Smarty()->assign( 'nn_validation_error', $_SESSION['nn_error'] );
			return false;
		}
		
		return true;
	}

    /**
	 * To check whether fraud prevention can be enabled or not
	 *
	 * @param string $payment
	 * @return bool
	 */
	public function canEnableFraudModule( $payment )
	{
		return !( ( $this->helper->getConfigurationParams( 'pin_amount', $payment ) && $this->helper->getConfigurationParams( 'pin_amount', $payment ) > 0 && ( ( $_SESSION['Warenkorb']->gibGesamtsummeWarenExt( array( C_WARENKORBPOS_TYP_ARTIKEL ), true ) * 100 ) < $this->helper->getConfigurationParams( 'pin_amount', $payment ) ) ) || ( !in_array( $_SESSION['Kunde']->cLand, array( 'DE','AT','CH' ) ) ) || $_SESSION['Zahlungsart']->nWaehrendBestellung == 0 );
	}

	/**
	 * Check when order amount has been changed after first payment call
	 *
	 * @param integer $currentOrderAmount
	 * @param string $payment
	 * @return none
	 */
	public function orderAmountCheck( $currentOrderAmount, $payment )
	{
		if ( $currentOrderAmount != $_SESSION['nn_amount'] ) 
		{
			$_SESSION['fraud_check_error'] = $this->oPlugin->oPluginSprachvariableAssoc_arr['__NN_amount_fraudmodule_error'];
			
			$this->helper->novalnetSessionCleanUp( $payment ); // Unset the entire novalnet session on order completion
			
			header( 'Location:' . Shop::getURL() . '/bestellvorgang.php?editZahlungsart=1' );
			exit();
		}
	}

	/**
	 * Checks & generates instance for the current class
	 *
	 * @param none
	 * @return object
	 */
	public static function getInstance()
	{
		if ( !isset( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
}
