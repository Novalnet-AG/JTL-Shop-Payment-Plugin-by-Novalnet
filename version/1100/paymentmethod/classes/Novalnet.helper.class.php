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
 * Script: Novalnet.helper.class.php
 *
 */

/**
 * Class NovalnetHelper
 */
class NovalnetHelper
{
	/**
     * @var Plugin
     */
	public $oPlugin;
	
	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->oPlugin = nnGetPluginObject();
	}

	/**
	 * Retrieve configuration values from Plugin array
	 *
	 * @param  string $configuration
	 * @param  boolean|string $payment
	 * @return mixed
	 */
	public function getConfigurationParams( $configuration, $payment = false )
	{		
		$configValue = $payment ? $payment . '_' . $configuration : $configuration;

		if ( isset( $this->oPlugin->oPluginEinstellungAssoc_arr[$configValue] ) ) {
			if ( $configValue == 'tariffid' ) {
				$tariffValue = trim( $this->oPlugin->oPluginEinstellungAssoc_arr['tariffid'] );
				$tariffId = explode( '-', $tariffValue );
				return $tariffId;
			}
			return trim( $this->oPlugin->oPluginEinstellungAssoc_arr[$configValue] );
		}
	}

	/**
	 * Used to encode the data
	 *
	 * @param string/double $data
	 * @return string
	 */
	public function generateEncode( $data )
	{
		$paymentKey = $this->getConfigurationParams( 'key_password' );
		
		if ( !empty( $_SESSION['nn_aff_id']) ) {
			$affDetails = $this->getAffiliateDetails(); // Get affiliate details for the current user
			$paymentKey = $affDetails->cAffAccesskey;
		}
		if ( !function_exists('base64_encode') or !function_exists('pack') or !function_exists('crc32') ) 
		  return false;

		try {
			$crc = sprintf( '%u', crc32( $data ) );
			$data = $crc . '|' . $data;
			$data = bin2hex( $data . $paymentKey );
			$data = strrev( base64_encode( $data ) );
		} catch ( Exception $e ){
			echo ( 'Error: '.$e );
		}

		return $data;
	}

	/**
	 * To get the encoded array
	 *
	 * @param array $data
	 * @return array
	 */
	public function generateEncodeArray( &$data )
	{
		$parameterKeys = $this->getBasicParametersNames( $data['key'] ); // Form basic parameter keys before sending post back call
		
		$arrayValue = array( $parameterKeys['authcode'], $parameterKeys['product'], $parameterKeys['tariff'], 'test_mode', 'amount', 'uniqid' );

		foreach ( $arrayValue as $val ) {
			$data[$val] = $this->generateEncode( $data[$val] ); // Encodes the data
		}
	}

	/**
	 * To generate the hash value
	 *
	 * @param array $h
	 * @return string
	 */
	public function generateHashValue( &$h, $paymentKey )
	{
		$parameterKeys = $this->getBasicParametersNames( $paymentKey ); // Form basic parameter keys
		$paymentKey = $this->getConfigurationParams( 'key_password' );
		
		if ( !empty( $_SESSION['nn_aff_id'] ) ) {
			$affDetails = $this->getAffiliateDetails(); // Get affiliate details for the current user
			$paymentKey = $affDetails->cAffAccesskey;
		}
		
		return md5( $h[$parameterKeys['authcode']] . $h[$parameterKeys['product']] . $h[$parameterKeys['tariff']] . $h['amount'] . $h['test_mode'] . $h['uniqid'] . strrev( $paymentKey ) );
	}

	/**
	 * Used to decode the data
	 *
	 * @param string/bool $data
	 * @return string
	 */
	public function generateDecode( $data )
	{
		$paymentKey = $this->getConfigurationParams( 'key_password' );

		if ( !empty( $_SESSION['nn_aff_id'] ) ) {
			$affDetails = $this->getAffiliateDetails();
			$paymentKey = $affDetails->cAffAccesskey;
		}

		try {
			$data = base64_decode( strrev( $data ) );
			$data = pack('H'.strlen( $data ), $data);
			$data = substr( $data, 0, stripos( $data, $paymentKey ) );
			$pos  = strpos( $data, '|' );

				if ( $pos === false ) {
					return('Error: CKSum not found!');
				}
				$crc    = substr( $data, 0, $pos );
				$value  = trim( substr( $data, $pos+1 ) );
				if ( $crc !=  sprintf( '%u', crc32( $value ) ) ) {
					return('Error; CKSum invalid!');
				}
			return $value;
		} catch ( Exception $e ) {
			echo ('Error: ' . $e);
		}
	}

	/**
	 * To check transaction hash from response
	 *
	 * @param array $response
	 * @return bool
	 */
	public function checkResponseHash( $response )
	{
		$parameterKeys = $this->getBasicParametersNames( $response['key'] ); // Form basic parameter keys
		
		$h = array();
		
		$h[$parameterKeys['authcode']]  = $response[$parameterKeys['authcode']];
		$h[$parameterKeys['product']]  	= $response[$parameterKeys['product']];
		$h[$parameterKeys['tariff']]    = $response[$parameterKeys['tariff']];
		$h['amount']       			    = $response['amount'];
		$h['test_mode']   				= $response['test_mode'];
		$h['uniqid']      				= $response['uniqid'];

		return !( $response['hash2'] != $this->generateHashValue( $h, $response['key'] ) );
	}

	/**
	 * Returns names of the basic request parameters
	 *
	 * @param integer $paymentKey
	 * @return array
	 */
	public function getBasicParametersNames( $paymentKey )
	{
		return ( $paymentKey == 6 ? array( 'vendor' => 'vendor_id', 'authcode' => 'vendor_authcode', 'tariff' => 'tariff_id', 'product' => 'product_id') : array( 'vendor' => 'vendor', 'authcode' => 'auth_code', 'tariff' => 'tariff', 'product' => 'product' ) );
	}

	/**
	 * To get reference TID for one-click shopping
	 *
	 * @param string $payment
	 * @param string $value
	 * @return integer
	 */
	public function getPaymentReferenceValues( $payment, $value )
	{
		if ( $this->getConfigurationParams( 'extensive_option', $payment ) == '1' ) { 
			
			$storedValues = Shop::DB()->query('SELECT ' . $value . ' FROM xplugin_novalnetag_tnovalnet_status WHERE cZahlungsmethode="' . $payment . '" AND cMail="' . $_SESSION['Kunde']->cMail . '" AND bOnetimeshopping != 1 ORDER BY kSno DESC LIMIT 1', 1);
			
			if ( !empty( $storedValues->$value ) )
				return $storedValues->$value;
		}
	}

	/**
	 * Get affiliate order details
	 *
	 * @param none
	 * @return array
	 */
	public function getAffiliateDetails()
	{
		if ( empty( $_SESSION['nn_aff_id'] ) && !empty( $_SESSION['Kunde']->kKunde ) ) {
			$affCustomer = Shop::DB()->query( 'SELECT nAffId FROM xplugin_novalnetag_taff_user_detail WHERE cCustomerId="' . $_SESSION['Kunde']->kKunde . '" ORDER BY kId DESC LIMIT 1',1);

			$_SESSION['nn_aff_id'] = !empty( $affCustomer->nAffId ) ? $affCustomer->nAffId : '';
		}

		if ( !empty( $_SESSION['nn_aff_id'] ) ) {			
			$affDetails = Shop::DB()->query('SELECT cAffAuthcode, cAffAccesskey FROM xplugin_novalnetag_taffiliate_account_detail WHERE nAffId="' . $_SESSION['nn_aff_id'] . '"',1);

			$affDetails->vendorid = $_SESSION['nn_aff_id'];

			return $affDetails;
		}
	}

	/**
	 * Retrieving payment reference parameters for invoice payments
	 *
	 * @param string $paymentMethod
	 * @return array
	 */
	public function getInvoicePaymentsReferences( $paymentMethod )
	{
		$paymentReference = array( 'payment_reference1', 'payment_reference2','payment_reference3' );

		foreach ( $paymentReference as $ref ) {
			$params[] = $this->oPlugin->oPluginEinstellungAssoc_arr[$paymentMethod . '_' . $ref];
		}

		return $params;
	}

	/**
	 * Unset the novalnet current payment session
	 *
	 * @param  string $payment
	 * @return none
	 */
	public function novalnetSessionUnset( $payment )
	{
		$sessionArray = array( 'novalnet_sepa', 'novalnet_invoice' );
		
		foreach ( $sessionArray as $val ) {
			if ( $payment != $val ) {
				unset( $_SESSION[$val] );
			}
		}
	}

	/**
	 * Unset the entire novalnet session on order completion
	 *
	 * @param  string $payment
	 * @return none
	 */
	public function novalnetSessionCleanUp( $payment )
	{
		$sessionValues = array( 'booking', 'success', 'error', 'amount', 'payment', 'request', 'during_order', 'redirect' );
		
		foreach ( $sessionValues as $val ) {
			if ( isset( $_SESSION['nn_' . $val] ) )
				unset( $_SESSION['nn_' . $val] );
		}

		unset( $_SESSION[$payment . '_guarantee'] );
		unset( $_SESSION[$payment] );
	}

	/**
	 * Return during error
	 *
	 * @param array $paymentResponse
	 * @return none
	 */
	public function redirectOnError( $paymentResponse = array() )
	{
		if ( !empty( $_SESSION['nn_during_order'] ) ) {

			$orderObj = nnGetOrderObject( $paymentResponse['order_no'] ); // Get order object instance
			
			$transactionComments = !empty( $_SESSION['kommentar'] ) ? $_SESSION['kommentar'] . PHP_EOL . PHP_EOL . $orderObj->cZahlungsartName . PHP_EOL : $orderObj->cZahlungsartName . PHP_EOL;

			if ( !empty( $paymentResponse['tid'] ) ) {
				$transactionComments .= $this->oPlugin->oPluginSprachvariableAssoc_arr['__NN_tid_label'] . $paymentResponse['tid'] . PHP_EOL;
			}
			
			if ( !nnIsDigits( $paymentResponse['test_mode'] ) ) {
				$paymentResponse['test_mode'] = $this->generateDecode( $paymentResponse['test_mode'] );
			}

			if ( !empty( $paymentResponse['test_mode'] ) || $this->getConfigurationParams( 'testmode', $paymentResponse['inputval3'] ) != '' ) { // Condition to retrieve the testmode for the payment
				$transactionComments .= $this->oPlugin->oPluginSprachvariableAssoc_arr['__NN_test_order'] . PHP_EOL;
			}
			
			$transactionComments .= !empty ( $_SESSION['nn_error'] ) ? $_SESSION['nn_error'] : '';
	
			Shop::DB()->query( 'UPDATE tbestellung SET cKommentar = "' . $transactionComments . '", cStatus = -1 WHERE kBestellung = '. $orderObj->kBestellung, 10 );

			$this->novalnetSessionCleanUp( $paymentResponse['inputval3'] ); // Unset the entire novalnet session on order completion
			
			header( 'Location:' . Shop::getURL() . '/jtl.php?bestellung=' . $orderObj->kBestellung );
			exit();
		}		
		
		header( 'Location:' . Shop::getURL() . '/bestellvorgang.php?editZahlungsart=1' );
		exit();
	}
}

/**
 * Get language texts for the fields
 *
 * @param array $languageFields
 * @return array
 */	
function nnGetLanguageText( $languageFields ) {
	
	$PluginObj = nnGetPluginObject(); // Get plugin's instance

	foreach ( $languageFields as $language ) {
		$placeholder = str_replace( '__NN_', '', $language );
		$lang[$placeholder] = $PluginObj->oPluginSprachvariableAssoc_arr[$language];
	}
	
	return $lang;
}

/**
 * Get plugin object
 *
 * @param none
 * @return object
 */
function nnGetPluginObject()
{
	return Plugin::getPluginById('novalnetag');
}

/**
 * Get order object
 *
 * @param string $orderno
 * @return object $orderObj
 */
function nnGetOrderObject( $orderno )
{
	$order = Shop::DB()->query( 'SELECT kBestellung FROM tbestellung WHERE cBestellNr ="' . $orderno . '"', 1);
	$orderObj = new Bestellung( $order->kBestellung );

	return $orderObj;
}

/**
 * Get payment name from payment settings
 *
 * @param integer $paymentNo
 * @return string
 */
function nnGetPaymentName( $paymentNo )
{
	global $oSprache;

	$paymentMethod = gibZahlungsart( intval ( $paymentNo ) );
	$paymentName = Shop::DB()->query( 'SELECT cName FROM tzahlungsartsprache WHERE kZahlungsart = ' . $paymentMethod->kZahlungsart . ' AND cISOSprache = "' . $oSprache->cISOSprache . '"',1 );

	return $paymentName->cName;
}

/**
 * To get a unique string
 *
 * @param none
 * @return string
 */
function nnGetRandomString()
{
	$randomwordarray = explode(',', 'a,b,c,d,e,f,g,h,i,j,k,l,m,1,2,3,4,5,6,7,8,9,0');
	shuffle( $randomwordarray);
	return substr( implode ( $randomwordarray, '' ) , 0, 30 );
}

/**
 * Returns shop formatted version
 *
 * @param integer $value
 * @return double
 */
function nnGetFormattedVersion( $value )
{
	return number_format( $value/100, 2, '.' ,'' );
}

/**
 * Get current shop language
 *
 * @param none
 * @return string
 */
function nnGetShopLanguage()
{
	return $GLOBALS['oSprache']->cISOSprache == 'ger' ? 'DE' : 'EN';
}

/**
 * Get server address
 *
 * @param none
 * @return mixed
 */
function nnGetServerAddr()
{
	return $_SERVER['SERVER_ADDR'] == '::1' ? '127.0.0.1' : $_SERVER['SERVER_ADDR'];
}

/**
 * To check whether an element is digit
 *
 * @param string $element
 * @return bool
 */
function nnIsDigits( $element )
{
	return !empty( $element ) && preg_match( '/^[0-9]+$/', $element );
}
