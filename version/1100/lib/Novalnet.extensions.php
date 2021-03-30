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
 * Script: Novalnet.extensions.php
 *
 */ 
header( 'Access-Control-Allow-Origin: *' );

// Request
$request = $_REQUEST;

require_once( $request['pluginInc'] );

// Get plugin object
$oPlugin = Plugin::getPluginById( 'novalnetag' );

require_once( PFAD_ROOT . PFAD_PLUGIN . $oPlugin->cPluginID . '/' . PFAD_PLUGIN_VERSION . $oPlugin->nVersion . '/' . PFAD_PLUGIN_PAYMENTMETHOD  . PFAD_CLASSES .'Novalnet.abstract.class.php' );
require_once( PFAD_ROOT . PFAD_INCLUDES_MODULES . 'PaymentMethod.class.php' );
require_once( PFAD_ROOT . PFAD_CLASSES . 'class.JTL-Shop.Bestellung.php' );

$orderNo = $request['orderNo'];

$currency = NovalnetGateway::getPaymentCurrency( $orderNo ); // Get currency type for the current order

// Get language texts
$placeholder = array( '__NN_transaction_capture_text','__NN_transaction_void_text','__NN_refund_message','__NN_new_TID_message','__NN_amount_update_message','__NN_zero_booking_message','__NN_transaction_error','__NN_subscription_cancelled','__NN_test_order','__NN_tid_label' );

$apicallTexts = nnGetLanguageText( $placeholder ); // Get language texts for the fields

// Get order details
$orderDetails  = Shop::DB()->query('SELECT cKonfigurations, cZahlungsmethode, nNntid, nBetrag, cZeroBookingParams FROM xplugin_novalnetag_tnovalnet_status WHERE cNnorderid = "' . $orderNo . '"', 1);

$paymentMethod = $orderDetails->cZahlungsmethode; // Payment method for the order

// Get invoice details
if ( in_array( $paymentMethod, array( 'novalnet_invoice', 'novalnet_prepayment' ) ) ) {
	$transDetails = Shop::DB()->query( 'SELECT cbankName, cbankCity, cbankIban, cbankBic, bTestmodus, cReferenceValues FROM xplugin_novalnetag_tpreinvoice_transaction_details WHERE cBestellnummer = "' . $orderNo . '"', 1);
}

// Get order reference number
$orderRef = Shop::DB()->query('SELECT kBestellung FROM tbestellung WHERE cBestellNr = "' . $orderNo . '"', 1);

// Get Novalnet gateway class instance
$novalnetGateway = NovalnetGateway::getInstance();

// New order reference
$orderObj = new Bestellung( $orderRef->kBestellung );

$configDb = unserialize( $orderDetails->cKonfigurations );

$extensionServerRequest = array();

if ( $request['apiStatus'] == 'zeroBooking' ) { 
	$extensionServerRequest = unserialize( $orderDetails->cZeroBookingParams );
	$extensionServerRequest['amount'] = $request['bookAmount'];
	$extensionServerRequest['payment_ref'] = $orderDetails->nNntid;
	
	if ( $paymentMethod == 'novalnet_sepa' ) { // If the payment method is Novalnet SEPA, fetch duedate for transaction booking
		require_once( PFAD_ROOT . PFAD_PLUGIN . $oPlugin->cPluginID . '/' . PFAD_PLUGIN_VERSION . $oPlugin->nVersion . '/' . PFAD_PLUGIN_PAYMENTMETHOD  . PFAD_CLASSES . 'Novalnet.sepa.class.php' );
		
		$extensionServerRequest['sepa_due_date'] = NovalnetSepa::getSepaDuedate( $extensionServerRequest['sepa_date'] );
	}
} else {
	$extensionServerRequest = array(
		'vendor' 	=> $configDb['vendor'],
		'auth_code' => $configDb['auth_code'],
		'product'   => $configDb['product'],
		'tariff'    => $configDb['tariff'],
		'key' 	    => $configDb['key'],
		'tid' 	    => $orderDetails->nNntid
	);
	
	if ( $request['apiStatus'] == 'refund' ) { // Additional parameters for transaction refund request
		$extensionServerRequest['refund_request'] = 1;
		$extensionServerRequest['refund_param']   = $request['refundAmount'];
		
		if ( !empty( $request['refundRef'] ) ) 
			$extensionServerRequest['refund_ref'] = $request['refundRef'];
		
	} elseif ( $request['apiStatus'] == 'subsCancellation' ) { // Additional parameters for subscription cancellation request
		$extensionServerRequest['cancel_reason'] = $request['subsReason'];
		$extensionServerRequest['cancel_sub']	 = 1;
	} else {
		$extensionServerRequest['status']	     = 100;
		$extensionServerRequest['edit_status']   = 1;
		
		if ( $request['apiStatus'] == 'amountUpdate' ) { // Additional parameters for transaction amount update request
			$extensionServerRequest['update_inv_amount'] = 1;
			$extensionServerRequest['amount'] 	  		 = $request['amount'];
			
			if ( !empty( $request['dueDateChange'] ) ) // Additional parameter for due date update request
				$extensionServerRequest['due_date'] = $request['dueDateChange'];
							
		} elseif ( $request['apiStatus'] == 'void' ) 
			$extensionServerRequest['status'] = 103;
	}
}

if ( !empty( $extensionServerRequest ) ) {
	// Api extension call to server		
	$extensionResponse = http_get_contents( 'https://payport.novalnet.de/paygate.jsp', $novalnetGateway->getGatewayTimeout() , $extensionServerRequest ); // Core function - Make curl request call
	parse_str( $extensionResponse, $transactionResponse );

	if ( $request['apiStatus'] == 'zeroBooking' ) {
		$extensionServerRequest['tid'] = $transactionResponse['tid'];
	}

	// Response for the api call
	$apiResponse = $novalnetGateway->getResponseText( $transactionResponse );

	if ( $transactionResponse['status'] == 100 ) {  // Extension handling on success

		switch ( $request['apiStatus'] )
		{
			case 'capture':
			case 'void': // Capture and void process for transaction
			
				if ( $request['apiStatus'] == 'capture' ) {
					$transactionResponseMessage = sprintf( $apicallTexts['transaction_capture_text'], date('d.m.Y'), date('H:i:s') );
					$status = $oPlugin->oPluginEinstellungAssoc_arr['confirm_order_status'];

					if ( !in_array( $paymentMethod, array( 'novalnet_invoice', 'novalnet_prepayment' ) ) ) { // Invoice payments will be added to incoming transactions only when amount paid fully ( executed through callback script )
						$paymentName = $novalnetGateway->getPaymentMethod( $orderObj->kZahlungsart, false );
								
						$jtlPaymentmethod = new PaymentMethod( 'kPlugin_' . $oPlugin->kPlugin . '_' . $paymentName );

						$incomingPayment = new stdClass();
						$incomingPayment->fBetrag = $orderObj->fGesamtsumme;
						$incomingPayment->cISO = $currency;
						$incomingPayment->cHinweis = $extensionServerRequest['tid'];
						$jtlPaymentmethod->name = $orderObj->cZahlungsartName;
						$jtlPaymentmethod->addIncomingPayment( $orderObj, $incomingPayment ); // Adds the current transaction into the shop's order table

						NovalnetGateway::performDbExecution( 'tbestellung', 'dBezahltDatum = now()', 'cBestellNr = "' .$order->cBestellNr . '"' ); // Updates the value into the database
					}
					
				} else {
					$transactionResponseMessage = sprintf( $apicallTexts['transaction_void_text'], date('d.m.Y'), date('H:i:s') );
					$status = $oPlugin->oPluginEinstellungAssoc_arr['cancel_order_status'];
				}

				NovalnetGateway::performDbExecution( 'xplugin_novalnetag_tnovalnet_status', 'nStatuswert= ' . $transactionResponse['tid_status'], 'cNnorderid = "' . $orderNo . '"' ); // Updates the value into the database
				
				NovalnetGateway::performDbExecution( 'tbestellung', 'cStatus= ' . constant( $status ), 'cBestellNr = "' . $orderNo . '"' ); // Updates the value into the database
				
				break;
				
			case 'amountUpdate': // Amount update process for transaction	
	
				if ( $paymentMethod != 'novalnet_sepa' ) {

					NovalnetGateway::performDbExecution( 'xplugin_novalnetag_tpreinvoice_transaction_details', 'cRechnungDuedate = "' . $extensionServerRequest['due_date'] . '"', 'cBestellnummer ="' . $orderNo . '"' );

					$invoicePrepaymentDetails = array (
					  'invoice_bankname'  => $transDetails->cbankName,
					  'invoice_bankplace' => $transDetails->cbankCity,
					  'amount'            => ( $extensionServerRequest['amount'] / 100 ),
					  'currency'          => $currency,
					  'tid'               => $orderDetails->nNntid,
					  'invoice_iban'      => $transDetails->cbankIban,
					  'invoice_bic'       => $transDetails->cbankBic,
					  'due_date'		  => $extensionServerRequest['due_date'],
					  'product_id'	      => $configDb['product'],
					  'order_no'          => $orderNo,
					  'referenceValues'	  => $transDetails->cReferenceValues
					);

					$comments = PHP_EOL . $orderObj->cZahlungsartName . PHP_EOL;
					
					if ( $transDetails->bTestmodus ) {
						$comments .= $apicallTexts['test_order'] . PHP_EOL;
					}
					
					$comments .= $apicallTexts['tid_label'] . $orderDetails->nNntid . PHP_EOL;
					$comments .= $novalnetGateway->formInvoicePrepaymentComments( $invoicePrepaymentDetails, $currency, $paymentMethod, true );
				} else {
					NovalnetGateway::performDbExecution( 'xplugin_novalnetag_tcallback', 'nCallbackAmount =' . $extensionServerRequest['amount'], 'cBestellnummer ="' . $orderNo . '"' ); // Updates the value into the database
				}
				
				$transactionResponseMessage = sprintf( $apicallTexts['amount_update_message'], gibPreisString( ( $extensionServerRequest['amount'] / 100 ) ) . ' '. ( $currency ), date('d.m.Y'), date('H:i:s') ); 
				
				NovalnetGateway::performDbExecution( 'xplugin_novalnetag_tnovalnet_status', 'nStatuswert ="' . $transactionResponse['tid_status'] . '", nBetrag = "' . $extensionServerRequest['amount'] . '"', 'cNnorderid = "' . $orderNo . '"' ); // Updates the value into the database
				
				break;	

			case 'subsCancellation': // Subscription cancellation process for transaction

				if ( isset( $request['frontEnd'] ) ) { // If subscription cancelled through My account page
					$apicallTexts['subscription_cancelled'] = ( $orderObj->kSprache == 2 ) ? 'Subscription has been canceled due to:' : utf8_decode( 'Das Abonnement wurde gekündigt wegen:' );
				}
								
				$transactionResponseMessage = $apicallTexts['subscription_cancelled'] . ' ' . $request['subsReason'];

				NovalnetGateway::performDbExecution( 'tbestellung', 'cStatus=' . constant( $oPlugin->oPluginEinstellungAssoc_arr['subscription_order_status'] ), 'cBestellNr = "' . $orderNo . '"' ); // Updates the value into the database
				
				NovalnetGateway::performDbExecution( 'xplugin_novalnetag_tsubscription_details', 'cTerminationReason = "' . $request['subsReason'] . '", dTerminationAt = "' . date('Y-m-d H:i:s') . '"', 'cBestellnummer = "' . $orderNo . '"' ); // Updates the value into the database

				break;
			
			case 'zeroBooking': // Zero-amount process for transaction

				$transactionResponseMessage = sprintf( $apicallTexts['zero_booking_message'], ( $transactionResponse['amount'] . ' ' . $currency ), $extensionServerRequest['tid'] );

				if ( $transactionResponse['tid_status'] == 100 && !in_array( $paymentMethod, array( 'novalnet_invoice', 'novalnet_prepayment' ) ) ) { // Invoice payments will be added to incoming transactions only when amount paid fully ( executed through callback script )
					$paymentName = $novalnetGateway->getPaymentMethod( $orderObj->kZahlungsart, false );
							
					$jtlPaymentmethod = new PaymentMethod( 'kPlugin_' . $oPlugin->kPlugin . '_' . $paymentName );

					$incomingPayment = new stdClass();
					$incomingPayment->fBetrag = $orderObj->fGesamtsumme;
					$incomingPayment->cISO = $currency;
					$incomingPayment->cHinweis = $transactionResponse['tid'];
					$jtlPaymentmethod->name = $orderObj->cZahlungsartName;
					$jtlPaymentmethod->addIncomingPayment( $orderObj, $incomingPayment ); // Adds the current transaction into the shop's order table

					NovalnetGateway::performDbExecution( 'tbestellung', 'dBezahltDatum = now()', 'cBestellNr = "' .$order->cBestellNr . '"' ); // Updates the value into the database
				}
				
				NovalnetGateway::performDbExecution( 'xplugin_novalnetag_tnovalnet_status', 'nStatuswert= ' . $transactionResponse['tid_status'] . ', nNntid = ' . $transactionResponse['tid'] . ', nBetrag =' . ( $transactionResponse['amount'] * 100 ), 'cNnorderid = "' . $orderNo . '"' ); // Updates the value into the database

				NovalnetGateway::performDbExecution( 'xplugin_novalnetag_tcallback', 'nCallbackAmount =' . ( $transactionResponse['amount'] * 100 ), 'cBestellnummer ="' . $orderNo . '"' ); // Updates the value into the database

				break;
			
			default: // Refund process for transaction
			
				$transactionResponseMessage = sprintf( $apicallTexts['refund_message'], $extensionServerRequest['tid'], gibPreisString ( $request['refundAmount'] /100 ) . ' ' . $currency );

				if ( $extensionServerRequest['key'] == 34 && !empty( $transactionResponse['paypal_refund_tid'] ) ) {
					$transactionResponseMessage .= ' - PayPal Ref: ' . $transactionResponse['paypal_refund_tid']. '.';
				}

				if ( !empty( $transactionResponse['tid'] ) ) {
					$transactionResponseMessage .= $apicallTexts['new_TID_message'] . $transactionResponse['tid'];
				}

				if ( $transactionResponse['tid_status'] == 103 ) {				
					NovalnetGateway::performDbExecution( 'tbestellung', 'cStatus= ' . constant( $oPlugin->oPluginEinstellungAssoc_arr['cancel_order_status'] ), 'cBestellNr = "' . $orderNo . '"' ); // Updates the value into the database		 
				}

				NovalnetGateway::performDbExecution( 'xplugin_novalnetag_tnovalnet_status', 'nStatuswert= ' . $transactionResponse['tid_status'], 'cNnorderid = "' . $orderNo . '"' ); // Updates the value into the database

				break;
		}
		
		NovalnetGateway::performDbExecution( 'tbestellung', 'cKommentar = CONCAT(cKommentar, "' . ( !empty( $comments ) ? PHP_EOL . $comments : '' ) . PHP_EOL . $transactionResponseMessage . PHP_EOL . '")', 'cBestellNr = "' . $orderNo . '"' ); // Updates the value into the database
		
	} else { // Extension handling on error
	
		if ( in_array( $request['apiStatus'] , array( 'capture', 'void' ) ) ) {
			$transactionResponseMessage = $apiResponse  . '( Status : '. $transactionResponse['status'] .')';
		} else {
			$transactionResponseMessage = !empty( $apiResponse ) ? $apiResponse : utf8_decode($apicallTexts['transaction_error'] );
		}

		NovalnetGateway::performDbExecution( 'xplugin_novalnetag_tnovalnet_status', 'nStatuswert= ' . $transactionResponse['tid_status'] , 'cNnorderid = "' . $orderNo . '"' ); // Updates the value into the database
	}
	
	echo $transactionResponseMessage;
	exit();
}
