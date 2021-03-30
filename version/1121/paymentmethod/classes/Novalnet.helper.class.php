<?php
/**
 * Novalnet payment plugin
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Novalnet End User License Agreement
 *
 * DISCLAIMER
 *
 * If you wish to customize Novalnet payment extension for your needs,
 * please contact technic@novalnet.de for more information.
 *
 * @author  	Novalnet AG
 * @copyright  	Copyright (c) Novalnet
 * @license    	https://www.novalnet.de/payment-plugins/kostenlos/lizenz
 *
 * Script: Novalnet.helper.class.php
 *
 */
require_once PFAD_ROOT . PFAD_INCLUDES . 'plugin_inc.php';

global $shopUrl, $shopVersion, $shopQuery, $selectQuery;

// Condition to check for the shop series and assigns global variables accordingly
if (version_compare(JTL_VERSION, 400, '>=') && class_exists('Shop')) { // Condition to verify the higher JTLShop series (4x)
    global $DB, $smarty;

    // Shop variable to perform query function
    $shopQuery = 'query';

    // Shop variable to perform select single row operation
    $selectQuery = 'select';

    // Shop classes global DB object
    $DB = Shop::DB();

    // Shop class global Smarty object
    $smarty = Shop::Smarty();

    // The shop URL without trailing slash
    $shopUrl = Shop::getURL();

    // Shop series
    $shopVersion = '4x';
} else {
    // Shop variable to perform query function
    $shopQuery = 'executeQuery';

    // Shop variable to perform select single row operation
    $selectQuery = 'selectSingleRow';

    // The shop URL without trailing slash
    $shopUrl = gibShopURL();

    // Shop series
    $shopVersion = '3x';
}

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
    public function getConfigurationParams($configuration, $payment = false)
    {
        $configValue = $payment ? $payment . '_' . $configuration : $configuration;

        if (isset($this->oPlugin->oPluginEinstellungAssoc_arr[$configValue])) {
            if ($configValue == 'tariffid') {
                $tariffValue = trim($this->oPlugin->oPluginEinstellungAssoc_arr['tariffid']);
                $tariffId    = explode('-', $tariffValue);
                return $tariffId;
            }
            return trim($this->oPlugin->oPluginEinstellungAssoc_arr[$configValue]);
        }
    }
    
    /**
     * Get unique id
     *
     * @return int
     */
    public function get_uniqueid()
    {
        $randomwordarray = explode(',', '8,7,6,5,4,3,2,1,9,0,9,7,6,1,2,3,4,5,6,7,8,9,0');
        shuffle($randomwordarray);
        return substr(implode($randomwordarray, ''), 0, 16);
    }
    
    /**
     * Used to encode the data
     *
     * @param string/double $data
     * @param int $uniqid
     * @return mixed
     */
    public  function generateEncode($data, $uniqid) 
    {
        
        return htmlentities(base64_encode(openssl_encrypt($data, "aes-256-cbc", $this->getConfigurationParams('key_password'), true, $uniqid)));
    }

    /**
     * To get the encoded array
     *
     * @param array $data
     * @param int $uniqid
     * @return array
     */
    public function generateEncodeArray(&$data, $uniqid)
    {
	   foreach (array('auth_code', 'product', 'tariff', 'test_mode', 'amount') as $val) {                  
		   $data[$val] = $this->generateEncode($data[$val], $uniqid); // Encodes the data
	   }
	   
	   $data['hash'] = $this->generateHashValue($data);
    }

    /**
     * To generate the hash value
     *
     * @param array $h
     * @return string
     */
    public function generateHashValue($h)
    {
        return hash('sha256', ($h['auth_code'].$h['product'].$h['tariff'].$h['amount'].$h['test_mode'].$h['uniqid'].strrev($this->getConfigurationParams('key_password'))));
        
    }

    /**
     * Used to decode the data
     *
     * @param string/bool $data
     * @param int $uniqid
     * @return string
     */
    public function generateDecode($data,$uniqid)
    { 
                
        return openssl_decrypt(base64_decode($data), 'aes-256-cbc', $this->getConfigurationParams('key_password'), true, $uniqid); 
         
    }

    /**
     * To get reference details for one-click shopping
     *
     * @param string $payment
     * @param string $value
     * @return integer
     */
    public function getPaymentReferenceValues($payment, $value)
    {
        global $DB, $shopQuery;

        if ($this->getConfigurationParams('extensive_option', $payment) == '1' && !empty($_SESSION['Kunde']->kKunde)) {
            $storedValues = $DB->$shopQuery('SELECT ' . $value . ' FROM xplugin_novalnetag_tnovalnet_status WHERE cZahlungsmethode="' . $payment . '" AND cMail="' . $_SESSION['Kunde']->cMail . '" AND bOnetimeshopping != 1 AND cAdditionalInfo != "" ORDER BY kSno DESC LIMIT 1', 1);

            return (!empty($storedValues) ? $storedValues->$value : '');
        }
    }

    /**
     * To Updating the pluginId in tzahlungsart table
     *
     * @param  none
     * @return none
     */
    public function UpdatePluginId() 
    {
        global $oPlugin, $DB, $shopQuery;
        
        $oldPluginId = "kPlugin_".$oPlugin->kPlugin."_novalnet%";
        
        $oldPluginId_array = $DB->$shopQuery("SELECT cModulId FROM `tzahlungsart` WHERE `cModulId` LIKE '".$oldPluginId."'",2);
        
        $nn_payment_array = $this->getPaymentarray();
    
        $payment_array = $this->get_array($oldPluginId_array);        
        
        foreach($nn_payment_array as $val)
        {
            if(!in_array($val, $payment_array))
            {
                $explode_array = explode("_", $val);
                $DB->$shopQuery("update tzahlungsart set cModulId='" . "kPlugin_".$oPlugin->kPlugin."_".$explode_array[2]."' where cModulId='" . $val . "'", 4);    
            }
        }
    }
    
    /**
     * Get Payment List
     *
     * @param  none
     * @return array
     */
    public function getPaymentarray()
    {
        global $DB, $shopQuery;
        
        $payment_array_total=$DB->$shopQuery("SELECT cModulId FROM `tzahlungsart` WHERE `cModulId` LIKE '%novalnet%'",2);
        
        $nn_payment_array = $this->get_array($payment_array_total);
        
        return $nn_payment_array;
    }

    /**
     * Used for converting object to array
     *
     * @param  object $payment_object
     * @return array
     */

    public function get_array($payment_object)
    {
        foreach($payment_object as $val)
        {
            $payment_arr[]=$val->cModulId;
        }
        return $payment_arr;
    }

    /**
     * Get affiliate order details
     *
     * @param none
     * @return array
     */
    public function getAffiliateDetails()
    {
        global $DB, $shopQuery;

        if (empty($_SESSION['nn_aff_id']) && !empty($_SESSION['Kunde']->kKunde)) {
            $affCustomer = $DB->$shopQuery('SELECT nAffId FROM xplugin_novalnetag_taff_user_detail WHERE cCustomerId="' . $_SESSION['Kunde']->kKunde . '" ORDER BY kId DESC LIMIT 1', 1);

            $_SESSION['nn_aff_id'] = !empty($affCustomer->nAffId) ? $affCustomer->nAffId : '';
        }

        if (!empty($_SESSION['nn_aff_id'])) {
            $affDetails = $DB->$shopQuery('SELECT cAffAuthcode, cAffAccesskey FROM xplugin_novalnetag_taffiliate_account_detail WHERE nAffId="' . $_SESSION['nn_aff_id'] . '"', 1);

            $affDetails->vendorid = $_SESSION['nn_aff_id'];

            return $affDetails;
        }
    }

    /**
     * Unsets the other Novalnet payment sessions
     *
     * @param  string $payment
     * @return none
     */
    public function novalnetSessionUnset($payment)
    {
        $sessionArray = array(
                            'novalnet_cc',
                            'novalnet_sepa',
                            'novalnet_invoice',
                            'novalnet_prepayment',
                            'novalnet_banktransfer',
                            'novalnet_paypal',
                            'novalnet_ideal',
                            'novalnet_eps',
                            'novalnet_giropay',
                            'novalnet_przelewy24'
                        );

        foreach ($sessionArray as $val) {
            if ($payment != $val) {
                unset($_SESSION['nn_' . $val . '_tid']);
                unset($_SESSION[$val]);
            }
        }
    }

    /**
     * Unset the entire novalnet session on order completion
     *
     * @param  string $payment
     * @return none
     */
    public function novalnetSessionCleanUp($payment)
    {
        $sessionValues = array(
                                'booking',
                                'error',
                                'amount',
                                'payment',
                                'request',
                                'during_order',
                                'comments',
                                'key_password',
                                $payment . '_tid',
                                $payment . '_guarantee',
                                $payment . '_guarantee_error'
                            );

        foreach ($sessionValues as $val) {
            if (isset($_SESSION['nn_' . $val])) {
                unset($_SESSION['nn_' . $val]);
            }
        }

        unset($_SESSION[$payment]);
    }
}

/**
 * Get language texts for the fields
 *
 * @param array $languageFields
 * @return array
 */
function nnGetLanguageText($languageFields)
{

    $PluginObj = nnGetPluginObject(); // Get plugin's instance

    foreach ($languageFields as $language) {
        $placeholder        = str_replace('__NN_', '', $language);
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
 * Retrieves payment key for the payment method
 *
 * @param string $paymentMethod
 * @return integer $paymentKeys[$paymentMethod]
 */
function nnGetPaymentKey($paymentMethod)
{
    $paymentKeys = array(
        'novalnet_cc'           => 6,
        'novalnet_invoice'      => 27,
        'novalnet_prepayment'   => 27,
        'novalnet_banktransfer' => 33,
        'novalnet_paypal'       => 34,
        'novalnet_sepa'         => 37,
        'novalnet_ideal'        => 49,
        'novalnet_eps'          => 50,
        'novalnet_cashpayment'  => 59,
        'novalnet_giropay'      => 69,
        'novalnet_przelewy24'   => 78,
    );

    return $paymentKeys[$paymentMethod];
}

/**
 * Retrieves payment module ID from the tzahlungsart shop table using payment ID
 *
 * @param integer $paymentNo
 * @return string $paymentId->cModulId
 */
function nnGetPaymentModuleId($paymentNo)
{
    global $DB, $shopQuery;

    $paymentId = $DB->$shopQuery('SELECT cModulId FROM tzahlungsart WHERE kZahlungsart ="' . $paymentNo . '"', 1);

    return $paymentId->cModulId;
}

/**
 * Retrieves payment ID from the tzahlungsart shop table using the payment module ID
 *
 * @param integer $moduleId
 * @return string $paymentId->kZahlungsart
 */
function nnGetShopPaymentId($moduleId)
{
    global $DB, $shopQuery;

    $paymentId = $DB->$shopQuery('SELECT kZahlungsart FROM tzahlungsart WHERE cModulId ="' . $moduleId . '"', 1);

    return $paymentId->kZahlungsart;
}

/**
 * Returns shop formatted version
 *
 * @param integer $value
 * @return double
 */
function nnGetFormattedVersion($value)
{
    return number_format($value/100, 2, '.', '');
}

/**
 * Get current shop language
 *
 * @param none
 * @return string
 */
function nnGetShopLanguage()
{
    return StringHandler::convertISO2ISO639($_SESSION['cISOSprache']);
}

/**
 * Get server or remote address from the global variable
 *
 * @param string $addressType
 * @return mixed
 */
function nnGetIpAddress($addressType)
{
    if ($addressType == 'REMOTE_ADDR') {
        # Shop's core function that fetches the remote address
        $remoteAddress = getRealIp();
        return $remoteAddress;
    }
    return $_SERVER[$addressType];
}

/**
 * To check whether an element is digit
 *
 * @param string $element
 * @return bool
 */
function nnIsDigits($element)
{
    return !empty($element) && preg_match('/^[0-9]+$/', $element);
}

/**
 * Retrieves the language variables based on the end-user's order language
 *
 * @param integer $pluginId
 * @param integer $languageKey
 * @return array
 */
function nnLoadOrderLanguage($pluginId, $languageKey)
{
    return gibPluginSprachvariablen($pluginId, nnLoadLanguageIso($languageKey));
}

/**
 * Retrieves the language ISO code for the current language variable
 *
 * @param integer $languageKey
 * @return array
 */
function nnLoadLanguageIso($languageKey)
{
    return gibSprachKeyISO('', $languageKey);
}

/**
 * Returns the country name for the country ISO code given
 *
 * @param string $countryCode
 * @return string
 */
function nnGetCountryName($countryCode)
{
    global $DB, $shopQuery;

    $countryName = $DB->$shopQuery('SELECT cDeutsch, cEnglisch FROM tland WHERE cISO ="' . $countryCode . '"', 1);

    return $countryName;
}

/**
 * Returns the amount after the currency conversion (if required)
 *
 * @param none
 * @return integer
 */
function nnConvertAmount()
{
    global $DB, $shopQuery;

    $convertedAmount = convertCurrency($_SESSION['Warenkorb']->gibGesamtsummeWaren(true), $_SESSION['Waehrung']->cISO);

    if (empty($convertedAmount)) {
        $convertedAmount = $_SESSION['Warenkorb']->gibGesamtsummeWaren(true);
    }

    return gibPreisString($convertedAmount) * 100;
}
