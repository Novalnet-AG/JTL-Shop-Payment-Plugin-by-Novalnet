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
 * Script: Novalnet.creditcard.class.php
 *
 */ 
require_once( PFAD_ROOT . PFAD_INCLUDES_MODULES . 'PaymentMethod.class.php' ); 
require_once( 'Novalnet.abstract.class.php' );

/**
 * Class NovalnetCreditcard
 */
class NovalnetCreditcard extends PaymentMethod
{
	/**
     * @var string
     */
    public $paymentName;

    /**
     * @var null|NovalnetGateway
     */
	public $novalnetGateway = null;

	/**
     * @var string
     */
	public $name;

    /**
	 * Constructor
	 */
    public function __construct()
    {
		$this->paymentName = 'novalnet_cc';
		
		// Creates instance for the NovalnetGateway class
		$this->novalnetGateway = NovalnetGateway::getInstance();
		
		// Creates instance for the NovalnetHelper class
		$this->helper = new NovalnetHelper();
		
		// Retrieves Novalnet plugin object
		$this->oPlugin = nnGetPluginObject();
		
		// Sets and displays payment error
		$this->novalnetGateway->assignPaymentError();
		
		if ( !empty( $_SESSION['nn_error'] ) && empty( $_SESSION[$this->paymentName]['one_click_shopping'] ) )
			$_SESSION[$this->paymentName]['form_error'] = true;
	}

	/**
	 * Core function - Called on payment page 
	 *
	 * @param array $args_arr
	 * @return bool
	 */
    public function isValidIntern( $args_arr = array() )
    {		
       	return !( !$this->helper->getConfigurationParams( 'enablemode', $this->paymentName ) || $this->novalnetGateway->isConfigInvalid() );
    }

    /**
	 * Core function - Called when additional template is used
	 *
	 * @param object $aPost_arr
	 * @return bool
	 */
    public function handleAdditional( $aPost_arr )
    {		
	    $referenceTid = $this->helper->getPaymentReferenceValues( $this->paymentName, 'nNntid' ); // Gets reference TID for reference transaction

		if ( empty( $referenceTid ) || $this->helper->getConfigurationParams( 'cc3d_active_mode' ) ) { // Displays the payment form for one-click shopping only
			return true;
		}
		
		$placeholder = array( '__NN_credit_card_name', '__NN_credit_card_number','__NN_credit_card_date', '__NN_credit_card_month', '__NN_credit_card_year','__NN_credit_card_cvc', '__NN_credit_card_error','__NN_javascript_error', '__NN_testmode', '__NN_card_details_link_old', '__NN_card_details_link_new', '__NN_credit_card_desc', '__NN_credit_card_type', '__NN_redirection_text', '__NN_redirection_browser_text' );

		Shop::Smarty()->assign( array(
			'payment_name'			=> !empty( $aPost_arr['Zahlungsart'] ) ? nnGetPaymentName( $aPost_arr['Zahlungsart'] ) : '',
			'test_mode'         	=> $this->helper->getConfigurationParams( 'testmode', $this->paymentName ),
			'paymentMethodURL'      => Shop::getURL() . '/' . PFAD_PLUGIN . $this->oPlugin->cVerzeichnis . '/' . PFAD_PLUGIN_VERSION . $this->oPlugin->nVersion . '/' . PFAD_PLUGIN_PAYMENTMETHOD,
			'form_error'			=> !empty( $_SESSION[$this->paymentName]['form_error'] ) ? $_SESSION[$this->paymentName]['form_error'] : '',
			'nn_lang'               => nnGetLanguageText( $placeholder ) // Get language texts for the variables
		) );

		if ( $this->helper->getConfigurationParams( 'extensive_option', $this->paymentName ) == '1' && !empty( $referenceTid ) ) { // Condition to check reference transaction 
		
			Shop::Smarty()->assign( 'one_click_shopping', true );
			Shop::Smarty()->assign( 'nn_saved_details', unserialize( $this->helper->getPaymentReferenceValues( $this->paymentName, 'cMaskedDetails' ) ) );
		}		

		if ( !$this->novalnetGateway->basicValidationOnhandleAdditional() ) { // Validation on displaying payment form before submission
			return false;
		} elseif ( isset( $aPost_arr['nn_payment'] ) ) { // Assigns post values to the payment session for later use
			$_SESSION[$this->paymentName] = array_map( 'trim', $aPost_arr );
			return true;	
		}		
	}

	/**
	 * Core function - Called when the additional template is submitted
	 *
	 * @param none
	 * @return bool
	 */
    public function validateAdditional()
    {
		return false;
	}
	
	/**
	 * Core function - Called at the time when 'Buy now' button is clicked, initialise the Payment process 
	 *
	 * @param object $order
	 * @return none|bool
	 */
    public function preparePaymentProcess( $order )
    {
		$sessionHash = $this->generateHash( $order ); // Core function - To generate session hash
		
		$paymentRequestParameters = $this->novalnetGateway->generatePaymentParams( $order, $this->paymentName ); // Retrieves payment parameters for the transaction
	
		$paymentRequestParameters['key'] = 6;
		$paymentRequestParameters['payment_type'] = 'CREDITCARD';
		$paymentRequestParameters['input3']	= 'payment';
		$paymentRequestParameters['inputval3'] = $this->paymentName;

		if ( $this->helper->getConfigurationParams( 'extensive_option', $this->paymentName ) == '2' ) { // Condition to check zero amount booking
			$_SESSION['nn_booking'] = $paymentRequestParameters;
		}

		if ( $this->helper->getConfigurationParams( 'cc3d_active_mode' ) ) { // If the credit card is 3D secured
			$paymentRequestParameters['cc_3d'] = 1;
		} elseif ( !empty( $_SESSION[$this->paymentName]['one_click_shopping'] ) ) { // Condition to check reference transaction
			$paymentRequestParameters['payment_ref'] = $this->helper->getPaymentReferenceValues( $this->paymentName, 'nNntid' );
			$paymentRequestParameters['cc_cvc2'] = $_SESSION[$this->paymentName]['nn_cvvnumber'];	
		}
			
		$this->novalnetGateway->preValidationCheckOnSubmission( $paymentRequestParameters ); // Validates whether the transaction can be passed to the server

		if ( $this->helper->getConfigurationParams( 'cc3d_active_mode' ) || empty( $_SESSION[$this->paymentName]['one_click_shopping'] ) ) { // If the credit card payment is not processed as a reference payment

			$handlerUrlParameters = $this->getPaymentReturnUrls( $sessionHash, $order->kBestellung ); // Retrives return URL's for redirection payment
			
			$paymentRequestParameters['return_url']         = $handlerUrlParameters['cReturnURL'];
			$paymentRequestParameters['return_method']      = 'POST';
			$paymentRequestParameters['error_return_url']   = $handlerUrlParameters['cFailureURL'];
			$paymentRequestParameters['error_return_method']= 'POST';
			$paymentRequestParameters['session']            = session_id();
			$paymentRequestParameters['user_variable_0']    = Shop::getURL();
			$paymentRequestParameters['uniqid']    		    = uniqid();
			$paymentRequestParameters['vendor_id'] 		    = $paymentRequestParameters['vendor'];
			$paymentRequestParameters['vendor_authcode']    = $paymentRequestParameters['auth_code'];
			$paymentRequestParameters['tariff_id'] 		    = $paymentRequestParameters['tariff'];
			$paymentRequestParameters['product_id'] 	    = $paymentRequestParameters['product'];
			$paymentRequestParameters['implementation']     = 'PHP_PCI';

			unset( $paymentRequestParameters['vendor'], $paymentRequestParameters['auth_code'], $paymentRequestParameters['tariff'], $paymentRequestParameters['product'] );
		
			$this->helper->generateEncodeArray( $paymentRequestParameters );
			$paymentRequestParameters['hash'] = $this->helper->generateHashValue( $paymentRequestParameters, 6 ); // Encodes the basic payment parameters before sending to third party

			Shop::Smarty()->assign( array(
				'paymentUrl'  	   => 'https://payport.novalnet.de/pci_payport',
				'datas'       	   => $paymentRequestParameters,
				'is_iframe'	  	   => $this->helper->getConfigurationParams( 'cc_form_mode' ) == '0',
				'message'     	   => $this->oPlugin->oPluginSprachvariableAssoc_arr['__NN_redirection_text'],
				'browser_message'  => $this->oPlugin->oPluginSprachvariableAssoc_arr['__NN_redirection_browser_text'],
				'button_text'      => $this->oPlugin->oPluginSprachvariableAssoc_arr['__NN_redirection_button_text'],
				'paymentMethodURL' => Shop::getURL() . '/' . PFAD_PLUGIN . $this->oPlugin->cVerzeichnis . '/' . PFAD_PLUGIN_VERSION . $this->oPlugin->nVersion . '/' . PFAD_PLUGIN_PAYMENTMETHOD,
				'is_new_call' => empty( $_SESSION[$this->paymentName]['one_click_shopping'] )
			) );
			if ( $this->helper->getConfigurationParams( 'cc_form_mode' ) == '0' ) { // Loads Creditcard PCI form in Iframe into shop template
				Shop::Smarty()->assign( 'content',  Shop::Smarty()->fetch( str_replace( 'frontend', 'paymentmethod', $this->oPlugin->cFrontendPfad ) . '/template/novalnet_cc_iframe.tpl' ) );
			}

			return false;
		}
		
		$_SESSION['nn_request'] = $paymentRequestParameters;

		if ( $_SESSION['Zahlungsart']->nWaehrendBestellung == 0 ) { // If the payment is done through after order completion process
			$_SESSION['nn_during_order'] = TRUE;
			
			$this->novalnetGateway->performServerCall( $this->paymentName ); // Do server call when payment before order completion option is set to 'Nein'

			header( 'Location:' . $this->getNotificationURL( $sessionHash ) . '&ph=' .$sessionHash );
			exit();				
		} else { // If the payment is done through during ordering process
			header( 'Location:' . $this->getNotificationURL( $sessionHash ) . '&sh=' . $sessionHash );
			exit();
		}
	}

	/**
	 * Core function - Called on notification URL
	 *
	 * @param object $order
	 * @param string $hash
	 * @param array  $args
	 * @return bool
	 */
	public function finalizeOrder( $order, $hash, $args )
    {		
		if ( !empty( $_SESSION[$this->paymentName]['one_click_shopping'] ) ) {
			// Condition to check process reference payment call
			$this->novalnetGateway->performServerCall( $this->paymentName ); // Do server call when payment before order completion option is set to 'Ja'
		}
			
        return $this->novalnetGateway->verifyNotification( $order, empty( $_SESSION[$this->paymentName]['one_click_shopping'] ) ? $args : '' ); // Finalises the order based on response
    }

	/**
	 * Core function - Called when order is finalized and created on notification URL
	 *
	 * @param object $order
	 * @param string $paymentHash
	 * @param array  $args
	 * @return none
	 */
	public function handleNotification( $order, $paymentHash, $args )
    {
		$argsArray = array( 'tid_status' => $_SESSION['nn_success']['tid_status'] ? $_SESSION['nn_success']['tid_status'] : $args['tid_status'], 'tid' => $_SESSION['nn_success']['tid'] ? $_SESSION['nn_success']['tid'] : $args['tid'] ); // Forms response parameters either from session or the $args value
		
		$this->updateShopDatabase( $order, $argsArray ); // Adds the payment method into the shop and changes the order status		
		
        $this->novalnetGateway->handleViaNotification( $order, $this->generateHash( $order ), 6, empty( $_SESSION[$this->paymentName]['one_click_shopping'] ) ? $args : '' ); // Redirects to handle_notification URL
    }

    /**
	 * Set return URLs for redirection payments
	 *
	 * @param string $sessionHash
	 * @param string $orderValue
	 * @return array 
	 */
	public function getPaymentReturnUrls( $sessionHash, $orderValue )
	{
		if ( $_SESSION['Zahlungsart']->nWaehrendBestellung == 0 ) {
			$_SESSION['nn_during_order'] = TRUE;
			$handlerUrlParameters['cReturnURL']  = $this->getNotificationURL( $sessionHash ) . '&ph=' . $sessionHash;
			$handlerUrlParameters['cFailureURL'] = $this->getNotificationURL( $sessionHash );
		} else {
			$handlerUrlParameters['cReturnURL']  = $handlerUrlParameters['cFailureURL'] = $this->getNotificationURL( $sessionHash ) . '&sh=' . $sessionHash;
		}

		return $handlerUrlParameters;
	}

    /**
	 * Adds the payment method into the shop, updates notification ID, sets order status
	 *
	 * @param object $order
	 * @param array  $argsArray
	 * @return none
	 */
	public function updateShopDatabase( $order, $argsArray )
	{		
		if ( $argsArray['tid_status'] == 100 && $this->helper->getConfigurationParams( 'extensive_option', $this->paymentName ) != '2' ) { // Adds to incoming payments only if the status is 100 or completed
			$incomingPayment = new stdClass();
			$incomingPayment->fBetrag = $order->fGesamtsummeKundenwaehrung;
			$incomingPayment->cISO = $order->Waehrung->cISO;
			$incomingPayment->cHinweis = $argsArray['tid'];
			$this->name = $order->cZahlungsartName; // Retrieves and assigns payment name to the payment method object 
			$this->addIncomingPayment( $order, $incomingPayment ); // Adds the current transaction into the shop's order table

			NovalnetGateway::performDbExecution( 'tbestellung', 'dBezahltDatum = now()', 'cBestellNr = "' .$order->cBestellNr . '"' ); // Updates the value into the database 
		}
		
		$this->updateNotificationID( $order->kBestellung, $argsArray['tid'] ); // Updates transaction ID into shop for reference
		
		NovalnetGateway::performDbExecution( 'tbestellung', 'cStatus=' . constant( $this->helper->getConfigurationParams( 'set_order_status', $this->paymentName ) ), 'cBestellNr = "' . $order->cBestellNr . '"' ); // Updates the value into the database 
	}
}	
