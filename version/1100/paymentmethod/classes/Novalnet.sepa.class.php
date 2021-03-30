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
 * Script: Novalnet.sepa.class.php
 *
 */  
require_once( PFAD_ROOT . PFAD_INCLUDES_MODULES . 'PaymentMethod.class.php' ); 
require_once( 'Novalnet.abstract.class.php' );

/**
 * Class NovalnetSepa
 */
class NovalnetSepa extends PaymentMethod
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
		$this->paymentName = 'novalnet_sepa';
		
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
       	return !( ( isset( $_SESSION[$this->paymentName . '_invalid'] ) && ( time() < $_SESSION[$this->paymentName . '_time_limit'] ) ) || !$this->helper->getConfigurationParams( 'enablemode', $this->paymentName ) || $this->novalnetGateway->isConfigInvalid() || !$this->novalnetGateway->checkGuaranteedPaymentOption( $this->paymentName, ( $_SESSION['Warenkorb']->gibGesamtsummeWarenExt( array( C_WARENKORBPOS_TYP_ARTIKEL ), true ) * 100 ) ) );
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

		if ( $this->novalnetGateway->displayFraudCheck( $this->paymentName ) && !empty( $_SESSION[$this->paymentName]['tid'] ) ) { // Skips the payment form for reply by e-mail
			return true;
		}
		
		$placeholder = array( '__NN_sepa_holder_name', '__NN_sepa_country_name','__NN_sepa_account_number', '__NN_sepa_bank_code','__NN_sepa_birthdate', '__NN_sepa_mandate_error','__NN_sepa_error', '__NN_sepa_mandate_text', '__NN_sepa_description', '__NN_javascript_error', '__NN_callback_phone_number','__NN_callback_sms', '__NN_callback_mail', '__NN_callback_pin','__NN_callback_forgot_pin', '__NN_callback_telephone_error','__NN_callback_mobile_error', '__NN_callback_email_pin', '__NN_callback_pin_error','__NN_callback_pin_error_empty', '__NN_testmode', '__NN_account_details_link_old', '__NN_account_details_link_new', '__NN_birthdate_error' );

		$affDetails = $this->helper->getAffiliateDetails(); // Get affiliate details for the current user

		Shop::Smarty()->assign( array(
			'payment_name'			=> !empty( $aPost_arr['Zahlungsart'] ) ? nnGetPaymentName( $aPost_arr['Zahlungsart'] ) : '',
			'vendor_id'        		=> empty( $affDetails->vendorid ) ? $this->helper->getConfigurationParams( 'vendorid' ) : $affDetails->vendorid,
			'auth_code'         	=> empty( $affDetails->cAffAuthcode ) ? $this->helper->getConfigurationParams( 'authcode' ) : $affDetails->cAffAuthcode,
			'test_mode'         	=> $this->helper->getConfigurationParams( 'testmode', $this->paymentName ),
			'uniq_value' 			=> nnGetRandomString(), // Generates unique ID for the payment
			'sepa_holder'          	=> $_SESSION['Kunde']->cVorname . ' ' . $_SESSION['Kunde']->cNachname,
			'is_payment_guarantee'  => !empty( $_SESSION[$this->paymentName . '_guarantee'] ) ? $_SESSION[$this->paymentName . '_guarantee'] : '',
			'paymentMethodURL'      => Shop::getURL() . '/' . PFAD_PLUGIN . $this->oPlugin->cVerzeichnis . '/' . PFAD_PLUGIN_VERSION . $this->oPlugin->nVersion . '/' . PFAD_PLUGIN_PAYMENTMETHOD,
			'country_list'		    => gibBelieferbareLaender( $_SESSION['Kunde']->kKundengruppe ),
			'lang_sepa_customer_email' => $_SESSION['Kunde']->cMail,
			'panhash'				=> $this->getSepaRefillHash(),
			'form_error'			=> !empty( $_SESSION[$this->paymentName]['form_error'] ) ? $_SESSION[$this->paymentName]['form_error'] : '',
			'nn_lang'               => nnGetLanguageText( $placeholder ), // Get language texts for the variables
		) );

		if ( $this->helper->getConfigurationParams( 'extensive_option', $this->paymentName ) == '1' && empty( $_SESSION[$this->paymentName]['tid'] ) && !empty( $referenceTid ) ) { // Condition to check reference transaction 
		
			Shop::Smarty()->assign( 'one_click_shopping', true );
			Shop::Smarty()->assign( 'nn_saved_details', unserialize( $this->helper->getPaymentReferenceValues( $this->paymentName, 'cMaskedDetails' ) ) );
		}		

		if ( !$this->novalnetGateway->basicValidationOnhandleAdditional( true ) ) { // Validation on displaying payment form before submission
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
		
		if ( !empty( $_SESSION[$this->paymentName]['tid'] ) ) {	// Perform second call for fraud module 
			$this->novalnetGateway->doSecondCall( $order, $this->paymentName );			
			header( 'Location:' . $this->getNotificationURL( $sessionHash ) . '&sh=' . $sessionHash );
			exit();
		} else {
			
			$paymentRequestParameters = $this->novalnetGateway->generatePaymentParams( $order,$this->paymentName ); // Retrieves payment parameters for the transaction
		
			$paymentRequestParameters['key'] = 37;
			$paymentRequestParameters['payment_type'] = 'DIRECT_DEBIT_SEPA';
			$paymentRequestParameters['input3']	= 'payment';
			$paymentRequestParameters['inputval3'] = $this->paymentName;

			if ( !empty( $_SESSION[$this->paymentName . '_guarantee'] ) ) { // Check to find whether the payment should be processed as a guaranteed payment
				$paymentRequestParameters['key'] = 40;
				$paymentRequestParameters['payment_type'] = 'GUARANTEED_DIRECT_DEBIT_SEPA';
				$paymentRequestParameters['birth_date'] = date( 'Y-m-d', strtotime( $_SESSION[$this->paymentName]['nn_dob'] ) );
			}

			if ( $this->helper->getConfigurationParams( 'extensive_option', $this->paymentName ) == '2' ) // Condition to check zero amount booking
				$_SESSION['nn_booking'] = $paymentRequestParameters;

			if ( !empty( $_SESSION[$this->paymentName]['one_click_shopping'] ) ) { // Condition to reference transaction
				$paymentRequestParameters['payment_ref'] = $this->helper->getPaymentReferenceValues( $this->paymentName, 'nNntid' );
			} else {
				$paymentRequestParameters['bank_account_holder']= $_SESSION[$this->paymentName]['nn_sepaowner'];
				$paymentRequestParameters['iban_bic_confirmed'] = 1;
				$paymentRequestParameters['sepa_unique_id']	= $_SESSION[$this->paymentName]['nn_sepaunique_id'];
				$paymentRequestParameters['sepa_hash']	    = $_SESSION[$this->paymentName]['nn_payment_hash'];
			}

			$paymentRequestParameters['sepa_due_date'] = self::getSepaDuedate( $this->helper->getConfigurationParams( 'sepa_due_date' ) ); // Retrieve sepa due date from configuration
			
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
		
        $this->novalnetGateway->handleViaNotification( $order, $this->generateHash( $order ), !empty( $_SESSION[$this->paymentName . '_guarantee'] ) ? 40 : 37 ); // Redirects to handle_notification URL
    }

    /**
	 * Adds the payment method into the shop, updates notification ID, sets order status
	 *
	 * @param object $order
	 * @return none
	 */
	public function updateShopDatabase( $order )
	{
		if ( $_SESSION['nn_success']['tid_status'] == 100 && $this->helper->getConfigurationParams( 'extensive_option', $this->paymentName ) != '2' ) { // Adds to incoming payments only if the status is 100 or completed
			$incomingPayment = new stdClass();
			$incomingPayment->fBetrag = $order->fGesamtsummeKundenwaehrung;
			$incomingPayment->cISO = $order->Waehrung->cISO;
			$incomingPayment->cHinweis = $_SESSION['nn_success']['tid'];
			$this->name = $order->cZahlungsartName; // Retrieves and assigns payment name to the payment method object
			$this->addIncomingPayment( $order, $incomingPayment ); // Adds the current transaction into the shop's order table

			NovalnetGateway::performDbExecution( 'tbestellung', 'dBezahltDatum = now()', 'cBestellNr = "' .$order->cBestellNr . '"' ); // Updates the value into the database 
		}

		$this->updateNotificationID( $order->kBestellung, $_SESSION['nn_success']['tid'] ); // Updates transaction ID into shop for reference
		
		NovalnetGateway::performDbExecution( 'tbestellung', 'cStatus=' . constant( $this->helper->getConfigurationParams( 'set_order_status', $this->paymentName ) ), 'cBestellNr = "' . $order->cBestellNr . '"' ); // Updates the value into the database 
	}

	/**
	 * Get panhash from database for sepa payment to process last successful order refill
	 *
	 * @param none
	 * @return string|null
	 */
	public function getSepaRefillHash()
	{
		$hashValue = Shop::DB()->query('SELECT cSepaHash FROM xplugin_novalnetag_tnovalnet_status WHERE cMail = "' . $_SESSION['Kunde']->cMail . '" ORDER BY kSno DESC LIMIT 1', 1);
		
		return ( $this->helper->getConfigurationParams( 'sepa_refill' ) && !empty( $hashValue->cSepaHash ) ? $hashValue->cSepaHash : ( $this->helper->getConfigurationParams( 'sepa_autorefill' ) ? $_SESSION[$this->paymentName]['nn_payment_hash'] : '' ) );
	}

	/**
	 * To get the Novalnet SEPA duedate in days
	 *
	 * @param integer $dueDate
	 * @return date
	 */
	public static function getSepaDuedate( $dueDate )
	{
		return ( nnIsDigits( $dueDate ) && $dueDate > 6 ) ? date( 'Y-m-d', strtotime( '+' . $dueDate . 'days' ) ) : date( 'Y-m-d', strtotime( '+7 days' ) );		
	}
}
