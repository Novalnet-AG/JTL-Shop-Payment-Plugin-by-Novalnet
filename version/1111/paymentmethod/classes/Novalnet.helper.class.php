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
require_once PFAD_ROOT . PFAD_INCLUDES . 'plugin_inc.php';

global $shopUrl, $shopVersion, $shopQuery;

// Condition to check for the shop series and assigns global variables accordingly
if (version_compare(JTL_VERSION, 400, '>=') && class_exists('Shop')) { // Condition to verify the higher JTLShop series (4x)
    global $DB, $smarty;
    // Shop variable to perform query function
    $shopQuery = 'query';
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
                $tariffId = explode('-', $tariffValue);
                return $tariffId;
            }
            return trim($this->oPlugin->oPluginEinstellungAssoc_arr[$configValue]);
        }
    }

    /**
     * Used to encode the data
     *
     * @param string/double $data
     * @return string
     */
    public function generateEncode($data)
    {
        $paymentKey = $this->getConfigurationParams('key_password');

        if (!empty($_SESSION['nn_aff_id'])) {
            $affDetails = $this->getAffiliateDetails(); // Get affiliate details for the current user
            $paymentKey = $affDetails->cAffAccesskey;
        }

        if (!function_exists('base64_encode') or !function_exists('pack') or !function_exists('crc32'))
          return false;

        try {
            $crc  = sprintf( '%u', crc32($data));
            $data = $crc . '|' . $data;
            $data = bin2hex($data . $paymentKey);
            $data = strrev(base64_encode($data));
        } catch (Exception $e){
            echo ('Error: '.$e);
        }

        return $data;
    }

    /**
     * To get the encoded array
     *
     * @param array $data
     * @return array
     */
    public function generateEncodeArray(&$data)
    {
        $arrayValue = array('auth_code', 'product', 'tariff', 'test_mode', 'amount', 'uniqid');

        foreach ($arrayValue as $val) {
            $data[$val] = $this->generateEncode($data[$val]); // Encodes the data
        }
    }

    /**
     * To generate the hash value
     *
     * @param array $h
     * @return string
     */
    public function generateHashValue(&$h)
    {
        $paymentKey = $this->getConfigurationParams('key_password');

        if (!empty($_SESSION['nn_aff_id'])) {
            $affDetails = $this->getAffiliateDetails(); // Get affiliate details for the current user
            $paymentKey = $affDetails->cAffAccesskey;
        }

        return md5($h['auth_code'] . $h['product'] . $h['tariff'] . $h['amount'] . $h['test_mode'] . $h['uniqid'] . strrev($paymentKey));
    }

    /**
     * Used to decode the data
     *
     * @param string/bool $data
     * @return string
     */
    public function generateDecode($data)
    {
        $paymentKey = $this->getConfigurationParams('key_password');

        if (!empty($_SESSION['nn_aff_id'])) {
            $affDetails = $this->getAffiliateDetails();
            $paymentKey = $affDetails->cAffAccesskey;
        }

        try {
            $data = base64_decode(strrev($data));
            $data = pack('H'.strlen($data), $data);
            $data = substr($data, 0, stripos($data, $paymentKey));
            $pos  = strpos($data, '|');

                if ($pos === false) {
                    return('Error: CKSum not found!');
                }
                $crc    = substr($data, 0, $pos);
                $value  = trim(substr($data, $pos+1));
                if ($crc !=  sprintf('%u', crc32($value))) {
                    return('Error; CKSum invalid!');
                }
            return $value;
        } catch (Exception $e) {
            echo ('Error: ' . $e);
        }
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

            $storedValues = $DB->$shopQuery('SELECT ' . $value . ' FROM xplugin_novalnetag_tnovalnet_status WHERE cZahlungsmethode="' . $payment . '" AND cMail="' . $_SESSION['Kunde']->cMail . '" AND bOnetimeshopping != 1 AND cMaskedDetails != "" ORDER BY kSno DESC LIMIT 1', 1);

            return (!empty($storedValues) ? $storedValues->$value : '');
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
        global $DB, $shopQuery;

        if (empty($_SESSION['nn_aff_id']) && !empty($_SESSION['Kunde']->kKunde)) {
            $affCustomer = $DB->$shopQuery('SELECT nAffId FROM xplugin_novalnetag_taff_user_detail WHERE cCustomerId="' . $_SESSION['Kunde']->kKunde . '" ORDER BY kId DESC LIMIT 1',1);

            $_SESSION['nn_aff_id'] = !empty($affCustomer->nAffId) ? $affCustomer->nAffId : '';
        }

        if (!empty($_SESSION['nn_aff_id'])) {
            $affDetails = $DB->$shopQuery('SELECT cAffAuthcode, cAffAccesskey FROM xplugin_novalnetag_taffiliate_account_detail WHERE nAffId="' . $_SESSION['nn_aff_id'] . '"',1);

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
    public function getInvoicePaymentsReferences($paymentMethod)
    {
        $paymentReference = array('payment_reference1', 'payment_reference2','payment_reference3');

        foreach ($paymentReference as $ref) {
            $params[] = $this->oPlugin->oPluginEinstellungAssoc_arr[$paymentMethod . '_' . $ref];
        }

        return $params;
    }

    /**
     * Unsets the other Novalnet payment sessions
     *
     * @param  string $payment
     * @return none
     */
    public function novalnetSessionUnset($payment)
    {
        $sessionArray = array('novalnet_cc', 'novalnet_sepa', 'novalnet_invoice', 'novalnet_prepayment','novalnet_banktransfer', 'novalnet_paypal', 'novalnet_ideal', 'novalnet_eps', 'novalnet_giropay', 'novalnet_przelewy24');

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
        $sessionValues = array('booking', 'error', 'amount', 'payment', 'request', 'during_order', $payment . '_tid', $payment . '_guarantee', $payment . '_guarantee_error', 'comments');

        foreach ($sessionValues as $val) {
            if (isset($_SESSION['nn_' . $val]))
                unset($_SESSION['nn_' . $val]);
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
function nnGetLanguageText($languageFields) {

    $PluginObj = nnGetPluginObject(); // Get plugin's instance

    foreach ($languageFields as $language) {
        $placeholder = str_replace('__NN_', '', $language);
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
        'novalnet_giropay'      => 69,
        'novalnet_przelewy24'   => 78,
    );

   return $paymentKeys[$paymentMethod];
}

/**
 * Retrieves payment module ID from the tzahlungsart shop table
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
 * To get a unique string
 *
 * @param none
 * @return string
 */
function nnGetRandomString()
{
    $randomwordarray = array('a','b','c','d','e','f','g','h','i','j','k','l','m','1','2','3','4','5','6','7','8','9','0');
    shuffle($randomwordarray);
    return substr(implode($randomwordarray, ''), 0, 30);
}

/**
 * Returns shop formatted version
 *
 * @param integer $value
 * @return double
 */
function nnGetFormattedVersion($value)
{
    return number_format($value/100, 2, '.' ,'');
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
 * Get server or remote address from the global variable
 *
 * @param string $addressType
 * @return mixed
 */
function nnGetIpAddress($addressType)
{
    return (filter_var($_SERVER[$addressType], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) || $_SERVER[$addressType] == '::1' ? '127.0.0.1' : $_SERVER[$addressType]);
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
