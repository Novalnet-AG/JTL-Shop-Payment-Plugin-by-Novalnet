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
 * Script: Novalnet.prepayment.class.php
 *
 */  
require_once( PFAD_ROOT . PFAD_INCLUDES_MODULES . 'PaymentMethod.class.php' ); 
require_once( 'Novalnet.abstract.class.php' );

/**
 * Class NovalnetPrepayment
 */
class NovalnetPrepayment extends PaymentMethod
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
	 * Constructor
	 */
    public function __construct()
    {
		$this->paymentName = 'novalnet_prepayment';
		
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
		$sessionHash = $this->generateHash( $order ); // Core function - To generate session hash
			
		$paymentRequestParameters = $this->novalnetGateway->generatePaymentParams( $order,$this->paymentName ); // Retrieves payment parameters for the transaction
	
		$paymentRequestParameters['key'] = 27;
		$paymentRequestParameters['invoice_type'] = 'PREPAYMENT';
		$paymentRequestParameters['payment_type'] = 'PREPAYMENT';
		$paymentRequestParameters['input3']	= 'payment';
		$paymentRequestParameters['inputval3'] = $this->paymentName;

		if ( $_SESSION['Zahlungsart']->nWaehrendBestellung == 0 ) {
			$paymentRequestParameters['invoice_ref'] = 'BNR-' . $paymentRequestParameters['product']  . '-' . $paymentRequestParameters['order_no'];
		}	
		
		$this->novalnetGateway->preValidationCheckOnSubmission( $paymentRequestParameters ); // Validates whether the transaction can be passed to the server
			
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
		if ( empty( $_SESSION[$this->paymentName]['tid'] ) ) { // Condition to check whether the first payment call has been processed already
			$this->novalnetGateway->performServerCall( $this->paymentName ); // Do server call when payment before order completion option is set to 'Ja'
		}		
		
		return $this->novalnetGateway->verifyNotification( $order ); // Finalises the order based on response
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
		$this->updateShopDatabase( $order ); // Adds the payment method into the shop and changes the order status

		$this->insertOrderIntoInvPaymentsTable( $order->cBestellNr ); // Insert values into xplugin_novalnetag_tpreinvoice_transaction_details table for amount update option

        $this->novalnetGateway->handleViaNotification( $order, $this->generateHash( $order ), 27 ); // Redirects to handle_notification URL
    }

     /**
	 * Adds the payment method into the shop, updates notification ID, sets order status
	 *
	 * @param object $order
	 * @return none
	 */
	public function updateShopDatabase( $order )
	{
		$this->updateNotificationID( $order->kBestellung, $_SESSION['nn_success']['tid'] ); // Updates transaction ID into shop for reference
		
		NovalnetGateway::performDbExecution( 'tbestellung', 'cStatus=' . constant( $this->helper->getConfigurationParams( 'set_order_status', $this->paymentName ) ), 'cBestellNr = "' . $order->cBestellNr . '"' ); // Updates the value into the database 
	}

	/**
	 * Logs Invoice bank details into database for later use
	 *
	 * @param string|integer $orderno
	 * @return none
	 */
	public function insertOrderIntoInvPaymentsTable( $orderno )
	{
		$insertPrepaymentDetails = new stdClass();
		$insertPrepaymentDetails->cBestellnummer    = $orderno;
		$insertPrepaymentDetails->bTestmodus     	= $_SESSION['nn_success']['test_mode'];
		$insertPrepaymentDetails->cbankName  		= $_SESSION['nn_success']['invoice_bankname'];
		$insertPrepaymentDetails->cbankCity  		= $_SESSION['nn_success']['invoice_bankplace'];
		$insertPrepaymentDetails->cbankIban  		= $_SESSION['nn_success']['invoice_iban'];
		$insertPrepaymentDetails->cbankBic  		= $_SESSION['nn_success']['invoice_bic'];
		$insertPrepaymentDetails->cRechnungDuedate  = $_SESSION['nn_success']['due_date'];
		$insertPrepaymentDetails->cReferenceValues  = serialize( $this->helper->getInvoicePaymentsReferences( $this->paymentName ) );

		Shop::DB()->insertRow( 'xplugin_novalnetag_tpreinvoice_transaction_details', $insertPrepaymentDetails );
	}
}
