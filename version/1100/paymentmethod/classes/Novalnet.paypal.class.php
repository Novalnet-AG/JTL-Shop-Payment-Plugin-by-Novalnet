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
 * Script: Novalnet.paypal.class.php
 *
 */  
require_once( PFAD_ROOT . PFAD_INCLUDES_MODULES . 'PaymentMethod.class.php' ); 
require_once( 'Novalnet.abstract.class.php' );

/**
 * Class NovalnetPaypal
 */
class NovalnetPaypal extends PaymentMethod
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
		$this->paymentName = 'novalnet_paypal';
		
		// Creates instance for the NovalnetGateway class
		$this->novalnetGateway = NovalnetGateway::getInstance();
		
		// Creates instance for the NovalnetHelper class
		$this->helper = new NovalnetHelper();
		
		// Retrieves Novalnet plugin object
		$this->oPlugin = nnGetPluginObject();
		
		// Sets and displays payment error
		$this->novalnetGateway->assignPaymentError();
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
	 * Core function - Called at the time when 'Buy now' button is clicked, initialise the Payment process
	 *
	 * @param object $order
	 * @return none
	 */
    public function preparePaymentProcess( $order )
    {
		$sessionHash = $this->generateHash( $order ); // Core - Generate session hash to complete the order
		
		$paymentRequestParameters = $this->novalnetGateway->generatePaymentParams( $order,$this->paymentName ); // Retrieves payment parameters for the transaction
	
		$paymentRequestParameters['key'] = 34;
		$paymentRequestParameters['payment_type'] = 'PAYPAL';
		$paymentRequestParameters['input3']	= 'payment';
		$paymentRequestParameters['inputval3'] = $this->paymentName;

		$this->novalnetGateway->preValidationCheckOnSubmission( $paymentRequestParameters ); // Validates whether the transaction can be passed to the server

		$handlerUrlParameters = $this->getPaymentReturnUrls( $sessionHash, $order->kBestellung ); // Retrives return URL's for redirection payment
				
		$paymentRequestParameters['return_url']         = $handlerUrlParameters['cReturnURL'];
		$paymentRequestParameters['return_method']      = 'POST';
		$paymentRequestParameters['error_return_url']   = $handlerUrlParameters['cFailureURL'];
		$paymentRequestParameters['error_return_method']= 'POST';
		$paymentRequestParameters['session']            = session_id();
		$paymentRequestParameters['user_variable_0']    = Shop::getURL();
		$paymentRequestParameters['uniqid']    		    = uniqid();

		$this->helper->generateEncodeArray( $paymentRequestParameters );
		$paymentRequestParameters['hash'] = $this->helper->generateHashValue( $paymentRequestParameters, 34 ); // Encodes the basic payment parameters before sending to third party
		   
		Shop::Smarty()->assign( array(
			'paymentUrl'       => 'https://payport.novalnet.de/paypal_payport',
			'datas'            => $paymentRequestParameters,
			'message'          => $this->oPlugin->oPluginSprachvariableAssoc_arr['__NN_redirection_text'],
			'browser_message'  => $this->oPlugin->oPluginSprachvariableAssoc_arr['__NN_redirection_browser_text'],
			'button_text'      => $this->oPlugin->oPluginSprachvariableAssoc_arr['__NN_redirection_button_text'],
			'is_new_call' 	   => 1,
			'paymentMethodURL' => Shop::getURL() . '/' . PFAD_PLUGIN . $this->oPlugin->cVerzeichnis . '/' . PFAD_PLUGIN_VERSION . $this->oPlugin->nVersion . '/' . PFAD_PLUGIN_PAYMENTMETHOD
		) );
	}	

	/**
	 * Core function - Called on payment page
	 *
	 * @param object $order
	 * @param string $hash
	 * @param array  $args
	 * @return bool
	 */
	public function finalizeOrder( $order, $hash, $args )
    {				
        return $this->novalnetGateway->verifyNotification( $order, $args ); // Finalises the order based on response
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
		$this->updateShopDatabase( $order, $args['tid'], $args['tid_status'] ); // Adds the payment method into the shop and changes the order status
		
        $this->novalnetGateway->handleViaNotification( $order, $this->generateHash( $order ), 34, $args ); // Redirects to handle_notification URL
    }

    /**
	 * Adds the payment method into the shop, updates notification ID, sets order status
	 *
	 * @param object $order
	 * @param integer $notificationID
	 * @param integer $paypalStatus
	 * @return none
	 */
	public function updateShopDatabase( $order, $notificationID, $paypalStatus )
	{
		if ( $paypalStatus == 100 ) { // Adds to incoming payments only if the status is 100
			$incomingPayment = new stdClass();
			$incomingPayment->fBetrag = $order->fGesamtsummeKundenwaehrung;
			$incomingPayment->cISO = $order->Waehrung->cISO;
			$incomingPayment->cHinweis = $notificationID;
			$this->name = $order->cZahlungsartName; // Retrieves and assigns payment name to the payment method object
			$this->addIncomingPayment( $order, $incomingPayment ); // Adds the current transaction into the shop's order table

			NovalnetGateway::performDbExecution( 'tbestellung', 'dBezahltDatum = now()', 'cBestellNr = "' .$order->cBestellNr . '"' ); // Updates the value into the database 
		}

		$this->updateNotificationID( $order->kBestellung, $notificationID ); // Updates transaction ID into shop for reference
		
		NovalnetGateway::performDbExecution( 'tbestellung', 'cStatus=' . constant( $paypalStatus == 90 ? $this->helper->getConfigurationParams( 'paypal_pending_status' ) : $this->helper->getConfigurationParams( 'set_order_status', $this->paymentName ) ), 'cBestellNr = "' . $order->cBestellNr . '"' ); // Updates the value into the database 
	}

	/**
	 * Set return URLs for redirection payments
	 *
	 * @param string $sessionHash
	 * @param string $orderValue
	 * @return array $handlerUrlParameters
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
}
