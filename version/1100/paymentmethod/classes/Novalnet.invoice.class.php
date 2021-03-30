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
 * Script: Novalnet.invoice.class.php
 *
 */ 
require_once( PFAD_ROOT . PFAD_INCLUDES_MODULES . 'PaymentMethod.class.php' ); 
require_once( 'Novalnet.abstract.class.php' );

/**
 * Class NovalnetInvoice
 */
class NovalnetInvoice extends PaymentMethod
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
		$this->paymentName = 'novalnet_invoice';
		
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
       	return !( ( isset( $_SESSION[$this->paymentName . '_invalid'] ) && ( time() < $_SESSION[$this->paymentName . '_time_limit'] ) ) || !$this->helper->getConfigurationParams( 'enablemode', $this->paymentName ) || $this->novalnetGateway->isConfigInvalid() || !$this->novalnetGateway->checkGuaranteedPaymentOption( $this->paymentName, ( $_SESSION['Warenkorb']->gibGesamtsummeWarenExt( array( C_WARENKORBPOS_TYP_ARTIKEL ), true ) * 100 ) ) );
    }

    /**
	 * Core function - Called when additional template is used
	 *
	 * @param array $aPost_arr
	 * @return bool
	 */
    public function handleAdditional( $aPost_arr )
    {		
		if ( $this->novalnetGateway->displayFraudCheck( $this->paymentName ) && ( $_SESSION[$this->paymentName . '_guarantee'] == '' ) ) { // Displays the additional template only for fraud modules
			return true;
		}

		$placeholder = array( '__NN_callback_phone_number', '__NN_callback_sms', '__NN_callback_mail', '__NN_callback_pin', '__NN_callback_forgot_pin', '__NN_callback_telephone_error', '__NN_callback_mobile_error', '__NN_callback_email_pin', '__NN_callback_pin_error', '__NN_callback_pin_error_empty','__NN_testmode', '__NN_invoice_description', '__NN_birthdate_error' );

		Shop::Smarty()->assign( array(
			'payment_name'			=> !empty( $aPost_arr['Zahlungsart'] ) ? nnGetPaymentName( $aPost_arr['Zahlungsart'] ) : '',			
			'test_mode'         	=> $this->helper->getConfigurationParams( 'testmode', $this->paymentName ),
			'is_payment_guarantee'  => !empty( $_SESSION[$this->paymentName . '_guarantee'] ) ? $_SESSION[$this->paymentName . '_guarantee'] : '',
			'paymentMethodURL'      => Shop::getURL() . '/' . PFAD_PLUGIN . $this->oPlugin->cVerzeichnis . '/' . PFAD_PLUGIN_VERSION . $this->oPlugin->nVersion . '/' . PFAD_PLUGIN_PAYMENTMETHOD,
			'lang_invoice_customer_email' => $_SESSION['Kunde']->cMail,
			'nn_lang'               => nnGetLanguageText( $placeholder ) // Get language texts for the variables
		) );

		if ( !$this->novalnetGateway->basicValidationOnhandleAdditional() ) { // Validation on displaying payment form before submission
			return false;
		} elseif ( isset( $aPost_arr['nn_payment'] ) ) {

			$postArray = array_map( 'trim', $aPost_arr );

			if ( !empty( $_SESSION[$this->paymentName]['tid'] ) ) {
				$_SESSION['nn_fraudmodule'] = $postArray;
			} else {
				$_SESSION[$this->paymentName] = $postArray;
			}
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
	 * @return none
	 */
    public function preparePaymentProcess( $order )
    {
		$sessionHash = $this->generateHash( $order ); // Core function - To generate session hash
		
		if ( !empty( $_SESSION[$this->paymentName]['tid'] ) ) {	// Perform second call for fraud module after payment call success
			$this->novalnetGateway->doSecondCall( $order, $this->paymentName );	// Fraud module xml call to complete the order		
			header( 'Location:' . $this->getNotificationURL( $sessionHash ) . '&sh=' . $sessionHash );
			exit();
		} else {
			
			$paymentRequestParameters = $this->novalnetGateway->generatePaymentParams( $order,$this->paymentName ); // Retrieves payment parameters for the transaction
		
			$paymentRequestParameters['key'] = 27;
			$paymentRequestParameters['invoice_type'] = 'INVOICE';
			$paymentRequestParameters['payment_type'] = 'INVOICE';
			$paymentRequestParameters['input3']	= 'payment';
			$paymentRequestParameters['inputval3'] = $this->paymentName;

			if ( !empty( $_SESSION[$this->paymentName . '_guarantee'] ) ) { // Check to find whether the payment should be processed as a guaranteed payment
				$paymentRequestParameters['key'] = 41;
				$paymentRequestParameters['payment_type'] = 'GUARANTEED_INVOICE_START';
				$paymentRequestParameters['birth_date'] = date( 'Y-m-d', strtotime( $_SESSION[$this->paymentName]['nn_dob'] ) );
			}

			$invoiceDuedate = $this->getInvoiceDuedate(); // Calculates Invoice payment duedate for the order
			
			if ( !empty( $invoiceDuedate ) ) {
				$paymentRequestParameters['due_date'] = $invoiceDuedate;
			}
				
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
			$this->novalnetGateway->performServerCall( $this->paymentName, $order->fGesamtsumme ); // Do server call when payment before order completion option is set to 'Ja'
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

		$this->insertOrderIntoInvoiceTable( $order->cBestellNr ); // Insert values into xplugin_novalnetag_tpreinvoice_transaction_details table for amount update option

        $this->novalnetGateway->handleViaNotification( $order, $this->generateHash( $order ), !empty( $_SESSION[$this->paymentName . '_guarantee'] ) ? 41 : 27 ); // Redirects to handle_notification URL
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
	 * To get the Novalnet Invoice duedate in days
	 *
	 * @return integer|null
	 */
	private function getInvoiceDuedate()
	{
		return ( nnIsDigits( $this->helper->getConfigurationParams( 'invoice_duration' ) ) ? date( 'Y-m-d', strtotime( '+' . $this->helper->getConfigurationParams( 'invoice_duration' ) . ' days' ) ) : '' );
	}

	/**
	 * Logs Invoice bank details into database for later use
	 *
	 * @param string|integer $orderno
	 * @return none
	 */
	public function insertOrderIntoInvoiceTable( $orderno )
	{
		$insertInvoiceDetails = new stdClass();
		$insertInvoiceDetails->cBestellnummer   = $orderno;
		$insertInvoiceDetails->bTestmodus   	= isset( $_SESSION['nn_success']['test_mode'] ) ? $_SESSION['nn_success']['test_mode'] : $_SESSION[$this->paymentName]['test_mode'];
		$insertInvoiceDetails->cbankName  		= isset( $_SESSION['nn_success']['invoice_bankname']) ? $_SESSION['nn_success']['invoice_bankname'] : $_SESSION[$this->paymentName]['invoice_bankname'];
		$insertInvoiceDetails->cbankCity  		= isset( $_SESSION['nn_success']['invoice_bankplace']) ? $_SESSION['nn_success']['invoice_bankplace'] : $_SESSION[$this->paymentName]['invoice_bankplace'];
		$insertInvoiceDetails->cbankIban  		= isset( $_SESSION['nn_success']['invoice_iban'] ) ? $_SESSION['nn_success']['invoice_iban'] : $_SESSION[$this->paymentName]['invoice_iban'];
		$insertInvoiceDetails->cbankBic  		= isset( $_SESSION['nn_success']['invoice_bic'] ) ? $_SESSION['nn_success']['invoice_bic'] : $_SESSION[$this->paymentName]['invoice_bic'];
		$insertInvoiceDetails->cRechnungDuedate = isset( $_SESSION['nn_success']['due_date'] ) ? $_SESSION['nn_success']['due_date'] : $_SESSION[$this->paymentName]['due_date'];
		$insertInvoiceDetails->cReferenceValues = serialize( $this->helper->getInvoicePaymentsReferences( $this->paymentName ) );

		Shop::DB()->insertRow( 'xplugin_novalnetag_tpreinvoice_transaction_details', $insertInvoiceDetails );
	}
}
