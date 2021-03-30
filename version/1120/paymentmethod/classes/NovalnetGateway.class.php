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
 * Script: NovalnetGateway.class.php
 *
 */
require_once('Novalnet.helper.class.php');

/**
 * Class NovalnetGateway
 */
class NovalnetGateway
{
    /**
     * @var null|instance
     */
    public static $instance = null;

    /**
     * @var null|NovalnetHelper
     */
    public $helper = null;

    /**
     * @var string|null
     */
    public $tariffValues;

    /**
     * @var string
     */
    public $novalnetVersion = '11.2.0';

    /**
     * @var string
     */
    public $novalnetPaygateUrl = 'https://payport.novalnet.de/paygate.jsp';
    
    /**
     * @var string
     */
    public $novalnetAutoConfigUrl = 'https://payport.novalnet.de/autoconfig';

    /**
     * @var string
     */
    public $novalnetInfoportUrl = 'https://payport.novalnet.de/nn_infoport.xml';

    /**
     * Constructor
     */
    public function __construct()
    {
        ob_start();

        // Creates instance for the NovalnetHelper class
        $this->helper = new NovalnetHelper();

        // Assigns error message to the shop's variable $hinweis
        $this->assignPaymentError();

        // Sets up the mandatory merchant fields to be used up in the payment module
        $this->setupGlobalConfiguration();
    }

    /**
     * Sets up the mandatory merchant fields to be used up in the payment module
     *
     * @param null
     * @return null
     */
    public function setupGlobalConfiguration()
    {
        $this->vendor       = $this->helper->getConfigurationParams('vendorid');
        $this->auth_code    = $this->helper->getConfigurationParams('authcode');
        $this->product      = $this->helper->getConfigurationParams('productid');
         // Gets tariff values to extract tariff id and tariff type
        $this->tariffValues = $this->helper->getConfigurationParams('tariffid');
        $this->tariff       = $this->tariffValues[0];
        $this->access_key   = $this->helper->getConfigurationParams('key_password');
    }

    /**
     * Checks & assigns manual limit
     *
     * @param double $amount
     * @param string $payment
     * @return bool
     */
    public function doManualLimitCheck($amount, $payment)
    {
        $manualCheckLimit = $this->helper->getConfigurationParams('manual_check_limit', $payment);
        if($this->helper->getConfigurationParams('payment_action', $payment)) {
            if (!$manualCheckLimit) {
                return true;
            }

            return (nnIsDigits($manualCheckLimit) && $amount >= $manualCheckLimit);
        }

        return false;
    }

    /**
     * Build payment parameters to server
     *
     * @param object $order
     * @param string $payment
     * @return array
     */
    public function generatePaymentParams($order, $payment)
    {
        global $shopUrl;

        if (!empty($_SESSION['nn_aff_id'])) {
            $affDetails       = $this->helper->getAffiliateDetails(); // Get affiliate details for the current user
            $this->vendor     = $affDetails->vendorid;
            $this->auth_code  = $affDetails->cAffAuthcode;
            $this->access_key = $affDetails->cAffAccesskey;
        }

        $_SESSION['nn_key_password'] = $this->access_key;

        $orderAmount = nnConvertAmount(); // Form order amount in cents with conversion using shop function

        $amount = (($this->helper->getConfigurationParams('extensive_option', $payment) == '2' && $this->tariffValues[1] == 2) && empty($_SESSION['nn_' . $payment . '_guarantee'])) ? 0 : $orderAmount; // Assigns amount based on zero-amount enable option

        /******** Basic parameters *****/

        $paymentRequestParameters = array(
            'vendor'           => $this->vendor,
            'auth_code'        => $this->auth_code,
            'product'          => $this->product,
            'tariff'           => $this->tariff,
            'test_mode'        => (int) $this->helper->getConfigurationParams('testmode', $payment),
            'amount'           => $amount,
            'currency'         => $order->Waehrung->cISO,

        /******** Customer parameters *****/

            'remote_ip'        => nnGetIpAddress('REMOTE_ADDR'),
            'first_name'       => !empty($_SESSION['Kunde']->cVorname) ? $_SESSION['Kunde']->cVorname : $_SESSION['Kunde']->cNachname,
            'last_name'        => !empty($_SESSION['Kunde']->cNachname) ? $_SESSION['Kunde']->cNachname : $_SESSION['Kunde']->cVorname,
            'gender'           => 'u',
            'email'            => $_SESSION['Kunde']->cMail,
            'street'           => $_SESSION['Kunde']->cStrasse . ',' . $_SESSION['Kunde']->cHausnummer,
            'search_in_street' => 1,
            'city'             => $_SESSION['Kunde']->cOrt,
            'zip'              => $_SESSION['Kunde']->cPLZ,
            'language'         => nnGetShopLanguage(), // Returns the current shop language
            'lang'             => nnGetShopLanguage(),
            'country_code'     => $_SESSION['Kunde']->cLand,
            'country'          => $_SESSION['Kunde']->cLand,
            'customer_no'      => !empty($_SESSION['Kunde']->kKunde) ? $_SESSION['Kunde']->kKunde : 'guest',

        /******** System parameters *****/

            'system_name'      => 'jtlshop',
            'system_version'   => nnGetFormattedVersion(JTL_VERSION) . '_NN' . $this->novalnetVersion,
            'system_url'       => $shopUrl,
            'system_ip'        => nnGetIpAddress('SERVER_ADDR'), // Returns the IP address of the server
            'notify_url'       => $this->helper->getConfigurationParams('callback_notify_url')
        );

        if (!empty($_SESSION['Kunde']->cFirma)) { // Check if company field is given
            $paymentRequestParameters['company'] = $_SESSION['Kunde']->cFirma;
        }

        $telNumber = !empty($_SESSION[$payment]['nn_tel_number'])
                     ? $_SESSION[$payment]['nn_tel_number']
                     : $_SESSION['Kunde']->cTel;

        if ($telNumber != '') {
            $paymentRequestParameters['tel'] = $telNumber;
        }

        $mobNumber = !empty($_SESSION[$payment]['nn_mob_number'])
                     ? $_SESSION[$payment]['nn_mob_number']
                     : $_SESSION['Kunde']->cMobil;

        if ($mobNumber != '') {
            $paymentRequestParameters['mobile'] = $mobNumber;
        }

        /******** Additional parameters *****/

        if ($this->doManualLimitCheck($orderAmount, $payment)) { // Manual limit check for the order
            $paymentRequestParameters['on_hold'] = 1;
        }

        $referrerId = $this->helper->getConfigurationParams('referrerid');

        if (nnIsDigits($referrerId)) { // Condition to check if the Referrer ID is an integer
            $paymentRequestParameters['referrer_id'] = $referrerId;
        }

        // Send order number with the payment request for order complete process
        if ($_SESSION['Zahlungsart']->nWaehrendBestellung == 0) {
            $paymentRequestParameters['order_no'] = $order->cBestellNr;
        }
        // Condition to check if fraud module can be handled for the payment
        if ($this->verifyFraudModule($payment) && empty($_SESSION[$payment]['one_click_shopping'])) {
            if ($this->helper->getConfigurationParams('pin_by_callback', $payment) == '1') {
                $paymentRequestParameters['pin_by_callback']  = 1;
            } elseif ($this->helper->getConfigurationParams('pin_by_callback', $payment) == '2') {
                $paymentRequestParameters['pin_by_sms'] = 1;
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
    public function doSecondCall($order, $payment)
    {
        global $shopUrl;
        // Check when order amount has been changed after first payment call
        $this->orderAmountCheck($order->fGesamtsumme, $payment);
        $aryResponse = $this->getPinStatus($payment); // Do transaction XML call for fraud module

        if (isset($aryResponse['status']) && $aryResponse['status'] == '100') { // Return on successful response
            $_SESSION[$payment]['tid_status'] = $aryResponse['tid_status'];
            return true;
        } else { // Perform error response operations
            unset($_SESSION[$payment]['nn_forgot_pin']);

            if ($aryResponse['status'] == '0529006') {
                $_SESSION[$payment . '_invalid']    = true;
                $_SESSION[$payment . '_time_limit'] = time()+(30*60);
                unset($_SESSION['nn_' . $payment . '_tid']);
            } elseif ($aryResponse['status'] == '0529008') {
                unset($_SESSION['nn_' . $payment . '_tid']);
            }

            $_SESSION['nn_error'] = utf8_decode(!empty($aryResponse['status_message'])
                                    ? $aryResponse['status_message']
                                    : (!empty($aryResponse['pin_status']['status_message'])
                                        ? $aryResponse['pin_status']['status_message']
                                        : ''));

            header('Location:' . $shopUrl . '/bestellvorgang.php?editZahlungsart=1');
            exit;
        }
    }

    /**
     * Compare the hash generated for redirection payments
     *
     * @param array $response
     * @param array $order
     * @param string $paymentName
     * @return none
     */
    public function hashCheckForRedirects($response, $order, $paymentName)
    {
        // Condition to check whether the payment is redirect
        if ($response['hash2'] != $this->helper->generateHashValue($response)) {
            $_SESSION['nn_error'] = html_entity_decode($this->helper->oPlugin->oPluginSprachvariableAssoc_arr['__NN_hash_error']);

            $this->redirectOnError($order, $response, $paymentName); // Redirects to the error page
        }
    }

    /**
     * To display additional form fields for fraud prevention setup
     *
     * @param string $payment
     * @return none
     */
    public function displayFraudCheck($payment)
    {
        global $smarty;

        $this->helper->novalnetSessionUnset($payment); // Unsets the other Novalnet payment sessions

        if ($this->helper->getConfigurationParams('pin_by_callback', $payment) == '0'
            || !$this->verifyFraudModule($payment)) {
            return true;
        } else {
            if (isset($_SESSION['nn_' . $payment . '_tid'])) { // If fraud module option is enabled
                $smarty->assign('pin_enabled', $payment);
            } else {
                // If pin by callback enabled
                if ($this->helper->getConfigurationParams('pin_by_callback', $payment) == '1') {
                    $smarty->assign('pin_by_callback', $payment);
                  // If pin by sms enabled
                } elseif ($this->helper->getConfigurationParams('pin_by_callback', $payment) == '2') {
                    $smarty->assign('pin_by_sms', $payment);
                }
            }
        }
    }

    /**
     * Make CURL payment request to Novalnet server
     *
     * @param string $payment
     * @return none
     */
    public function performServerCall($payment)
    {
        // Core function to handle cURL call with the transaction request
        $transactionResponse = http_get_contents($this->novalnetPaygateUrl, $this->getGatewayTimeout(), $_SESSION['nn_request']); // Core function - CURL request to server
        parse_str($transactionResponse, $response);
        $_SESSION[$payment] = array_merge((array)$_SESSION[$payment], $response);
    }

    /**
     * Returns with error message on error
     *
     * @param array $order
     * @param array $response
     * @param string $paymentName
     * @return none
     */
    public function redirectOnError($order, $response, $paymentName)
    {
        global $shopUrl, $shopVersion;

        if ($_SESSION['Zahlungsart']->nWaehrendBestellung == 0 || !empty($_SESSION['nn_during_order'])) {

            if ($this->helper->getConfigurationParams('display_order_comments') == '1' && !in_array($paymentName, array('novalnet_invoice', 'novalnet_prepayment', 'novalnet_cashpayment', 'novalnet_cc'))) {
                $transactionComments = $this->formTransactionComments($response, $order, $paymentName, true); // Build the Novalnet order comments
                self::performDbExecution('tbestellung', 'cKommentar = "' . $transactionComments . '", cStatus = ' . BESTELLUNG_STATUS_STORNO . ', cAbgeholt="Y"', 'kBestellung ="' . $order->kBestellung . '"');
            } else {
                self::performDbExecution('tbestellung', 'cStatus = ' . BESTELLUNG_STATUS_STORNO . ', cAbgeholt="Y"', 'kBestellung ="' . $order->kBestellung . '"');
            }

            $jtlPaymentmethod = PaymentMethod::create($order->Zahlungsart->cModulId); // Instance of class PaymentMethod
            // Triggers cancellation mail template
            $jtlPaymentmethod->sendMail($order->kBestellung, MAILTEMPLATE_BESTELLUNG_STORNO);

            // Clears shop session based on shop version
            if ($shopVersion == '4x') {
                $session = Session::getInstance(true, true);
                $session->cleanUp();
            } else {
                raeumeSessionAufNachBestellung();
            }
            // Unsets the entire novalnet session on order completion
            $this->helper->novalnetSessionCleanUp($paymentName);
            header('Location:' . $order->BestellstatusURL);
            exit;
        }

        header('Location:' . $shopUrl . '/bestellvorgang.php?editZahlungsart=1');
        exit;
    }

    /**
     * Process when reordering the payment from My-account (for shop 3.x series)
     *
     * @param object $order
     * @return none
     */
    public function handleReorderProcess($order)
    {
        global $shopVersion, $shopUrl;

        if ($shopVersion == '3x') {
            if ($this->helper->getConfigurationParams('display_order_comments') == '1') {
                self::performDbExecution('tbestellung', 'cKommentar = CONCAT(cKommentar, "' . PHP_EOL . $this->helper->oPlugin->oPluginSprachvariableAssoc_arr['__NN_order_not_sucessful'] . '"), cStatus = ' . BESTELLUNG_STATUS_STORNO . ', cAbgeholt="Y"', 'kBestellung ="' . $order->kBestellung . '"');
            } else {
                self::performDbExecution('tbestellung', 'cStatus = ' . BESTELLUNG_STATUS_STORNO . ', cAbgeholt="Y"', 'kBestellung ="' . $order->kBestellung . '"');
             }

            header('Location:' . $shopUrl . '/jtl.php?bestellung=' . $order->kBestellung);
            exit;
        }
    }

    /**
     * Process while handling handle_notification URL
     *
     * @param array   $order
     * @param string  $sessionHash
     * @param integer $paymentKey
     * @param string $paymentName
     * @param array $response
     * @return none
     */
    public function handlePaymentCompletion($order, $sessionHash, $paymentKey, $paymentName, $response = array())
    {
        global $shopUrl, $DB, $shopQuery;

        if (empty($response)) {
            $response = $_SESSION[$paymentName];
        }

        if (!empty($response['status'])) { // Evaluate if the status is returned during notify call
            if (in_array($response['status'], array('90', '100'))) { // On successful payment server response
                if (!empty($_SESSION['nn_during_order'])) {
                    $updateWawi = '';

                    if (!empty($response['hash2'])) {
                        // Compares the hash generated for redirection payments
                        $this->hashCheckForRedirects($response, $order, $paymentName);
                    }

                    if ($this->helper->getConfigurationParams('display_order_comments') == '1') {
                        // Form transaction comments for the current order
                        $transactionComments = $this->formTransactionComments($response, $order, $paymentName);

                        self::performDbExecution('tbestellung', 'cKommentar = CONCAT(cKommentar, "' . $transactionComments . '")', 'kBestellung =' . $order->kBestellung); // Updates the value into the database
                    }

                    // Retrieves payment object from class PaymentMethod
                    $jtlPaymentmethod = PaymentMethod::create($order->Zahlungsart->cModulId);
                    // Triggers order updation mail template
                    $jtlPaymentmethod->sendMail($order->kBestellung, MAILTEMPLATE_BESTELLUNG_AKTUALISIERT);
                } else {
                    // Post back acknowledgement call to map the order into Novalnet server
                    $this->postBackCall($response, $order->cBestellNr, $paymentKey, $order->cKommentar, $paymentName);
                }
                // Logs the order details in Novalnet tables for success
                $this->insertOrderIntoDB($response, $order->kBestellung, $paymentKey, $paymentName);

                $updateWawi = 'Y';

                if ($response['tid_status'] == '100') {
                    $updateWawi = 'N';
                }

                self::performDbExecution('tbestellung', 'cAbgeholt="'. $updateWawi . '"', 'kBestellung =' . $order->kBestellung); // Updates the value into the database

                $this->getPaymentTestmode($response); // Returns test mode value based on the payment type

                if ($this->helper->oPlugin->oPluginEmailvorlageAssoc_arr['novalnetnotification']->cAktiv == 'Y'
                    && $this->helper->getConfigurationParams('testmode', $paymentName) == '0'
                    && $response['test_mode'] == '1') {
                    require_once PFAD_ROOT . PFAD_INCLUDES . 'mailTools.php';

                    $adminDetails = $DB->$shopQuery('SELECT cName, cMail from tadminlogin LIMIT 1', 1);

                    $oMail                     = new stdClass();
                    $oMail->tkunde             = $_SESSION['Kunde'];
                    $oMail->tkunde->cBestellNr = $order->cBestellNr;
                    $oMail->tkunde->kSprache   = gibStandardsprache(true)->kSprache;
                    $oMail->mail->toName       = $adminDetails->cName;

                    if (!empty($adminDetails->cMail)) {
                        $oMail->mail->toEmail  = $adminDetails->cMail;
                    }
                    // Triggers email notification to merchant when the order placed as test order
                    sendeMail('kPlugin_' . $this->helper->oPlugin->kPlugin . '_novalnetnotification', $oMail);
                }

                // Unset the entire novalnet session on order completion
                $this->helper->novalnetSessionCleanUp($paymentName);
                header('Location: ' . $shopUrl . '/bestellabschluss.php?i=' . $sessionHash);
                exit;
            } else {
                $_SESSION['nn_error'] = $this->getResponseText($response);
                 // Logs the order details in Novalnet tables for failure
                $this->insertOrderIntoDBForFailure($response, $order, $paymentName, $paymentKey);
                $this->redirectOnError($order, $response, $paymentName); // Returns with error message on error
            }
        } else {
            header('Location:' . $shopUrl . '/bestellvorgang.php?editZahlungsart=1');
            exit;
        }
    }

    /**
     * Process completing order
     *
     * @param array   $order
     * @param string  $sessionHash
     * @param string  $paymentName
     * @param array   $response
     * @return none
     */
    public function completeProcess($order, $sessionHash, $paymentName, $response = array())
    {
        global $shopUrl, $DB, $shopQuery;

        if (empty($response)) {
            $response = $_SESSION[$paymentName];
        }

        if (in_array($response['status'], array('90', '100'))) {
            // Unset the entire novalnet session on order completion
            $this->helper->novalnetSessionCleanUp($paymentName);

            header('Location: ' . $shopUrl . '/bestellabschluss.php?i=' . $sessionHash);
            exit;
        } else {
            $_SESSION['nn_error'] = $this->getResponseText($response);

            $this->redirectOnError($order, $response); // Returns with error message on error
        }
    }

    /**
     * Finalize the order
     *
     * @param array  $order
     * @param array  $payment
     * @param integer  $paymentKey
     * @param array  $response
     * @return bool
     */
    public function verifyNotification($order, $payment, $paymentKey, $response = array())
    {
        if (empty($response)) {
            $response = $_SESSION[$payment];
        }

        if (in_array($response['status'], array('90', '100'))) { // On successful payment server response
            if (!empty($response['hash2'])) {
                // Compares the hash generated for redirection payments
                $this->hashCheckForRedirects($response, $order, $payment);
            }

            if ($this->helper->getConfigurationParams('display_order_comments') == '1') {
                $_SESSION['nn_comments'] = $this->formTransactionComments($response, $order, $payment);
            }

            return true;
        } else { // Assign error text on payment server response
            $_SESSION['nn_error'] = $this->getResponseText($response); // Retrieves response status texts from response

            if (!empty($_SESSION['nn_during_order'])) {
                // Logs the order details in Novalnet tables for failure
                $this->insertOrderIntoDBForFailure($response, $order, $payment, $paymentKey);
            }
            unset($_SESSION[$payment]['tid']);
            $this->redirectOnError($order, $response, $payment); // Returns with error message on error

            return false;
        }
    }

    /**
     * Build the Novalnet order comments
     *
     * @param array $response
     * @param array $order
     * @param string $paymentName
     * @param bool $onError
     * @return string
     */
    public function formTransactionComments($response, $order, $paymentName, $onError = false)
    {
        
        $userComments = '';

        if (!empty($_SESSION['cPost_arr']['kommentar'])) {
            $userComments = (empty($_SESSION['nn_during_order']) ? $_SESSION['cPost_arr']['kommentar'] : '') . PHP_EOL
                           ;
        }
        // Concatenate the Novalnet transaction comments with the user comments if given
        $transactionComments = $userComments . $order->cZahlungsartName . PHP_EOL;
        
        if (!empty($_SESSION['nn_' . $paymentName . '_guarantee'])) {
            $novalnetOrderLanguage = nnLoadOrderLanguage($this->helper->oPlugin->kPlugin, $order->kSprache);
            $transactionComments .= $novalnetOrderLanguage['__NN_guarantee_payments_comments'] . PHP_EOL;
        }

        if (!empty($response['tid'] )) {
            $transactionComments .= $this->helper->oPlugin->oPluginSprachvariableAssoc_arr['__NN_tid_label']
            . $response['tid'] . PHP_EOL;
        }

        $this->getPaymentTestmode($response); // Returns test mode value based on the payment type

        if (!empty($response['test_mode'])) { // Condition to retrieve the testmode for the payment
            $transactionComments .= $this->helper->oPlugin->oPluginSprachvariableAssoc_arr['__NN_test_order'] . PHP_EOL;
        }

        $transactionComments .= !empty($_SESSION['nn_error'])
                                ? $_SESSION['nn_error']
                                : ($response['status'] != '100' ? $this->getResponseText($response) . PHP_EOL : '');

        if (!$onError) {
            $response['kSprache'] = $order->kSprache;
            if (in_array($paymentName, array('novalnet_invoice', 'novalnet_prepayment')) && in_array($response['tid_status'],array('91','100'))) {
                $invoicePaymentsComments = $this->formInvoicePrepaymentComments($response, $order->Waehrung->cISO);
                $transactionComments    .= $invoicePaymentsComments;
            } elseif ($paymentName == 'novalnet_cashpayment') {
                $cashpaymentComments  = $this->prepareCashpaymentComments($response);
                $transactionComments .= $cashpaymentComments;
            }
            
            if ($response['tid_status'] == '75' && $paymentName=='novalnet_invoice') {
                $transactionComments .= PHP_EOL . $this->helper->oPlugin->oPluginSprachvariableAssoc_arr['__NN_invoice_gurantee_pending_payment_text'];             
            } elseif ($response['tid_status'] == '75' && $paymentName=='novalnet_sepa') {
                $transactionComments .= PHP_EOL . $this->helper->oPlugin->oPluginSprachvariableAssoc_arr['__NN_sepa_gurantee_pending_payment_text'];             
            }
        }

        return $transactionComments;
    }

    /**
     * Returns test mode value based on the payment type
     *
     * @param array  $response
     * @return none
     */
    public function getPaymentTestmode(&$response)
    {
        if (!preg_match('/^[0-9]+$/', $response['test_mode'])) {
            $response['test_mode'] = $this->helper->generateDecode($response['test_mode'], $response['uniqid']);
        }
    }

    /**
     * Form invoice & prepayment payments comments
     *
     * @param array  $response
     * @param string $currency
     * @param bool   $updateAmount
     * @return string
     */
    public function formInvoicePrepaymentComments($response, $currency, $updateAmount = false)
    {
        $response = array_map('utf8_decode', $response);

        // Retrieves the language variables based on the end-user's order language
        $novalnetOrderLanguage = nnLoadOrderLanguage($this->helper->oPlugin->kPlugin, $response['kSprache']);

        $orderNo = !empty($response['order_no']) ? $response['order_no'] : 'NN_ORDER';

        $duedate = new DateTime($response['due_date']);
        $transComments  = PHP_EOL . $novalnetOrderLanguage['__NN_invoice_payments_comments'] . PHP_EOL;
        $transComments .= $novalnetOrderLanguage['__NN_invoice_duedate'] . $duedate->format('d.m.Y') . PHP_EOL;
        $transComments .= $novalnetOrderLanguage['__NN_invoice_payments_holder'] . $response['invoice_account_holder'] . PHP_EOL;
        $transComments .= 'IBAN: ' . $response['invoice_iban'] . PHP_EOL;
        $transComments .= 'BIC:  ' . $response['invoice_bic'] . PHP_EOL;
        $transComments .= 'Bank: ' . $response['invoice_bankname'] . ' ' . $response['invoice_bankplace'] . PHP_EOL;
        $transComments .= $novalnetOrderLanguage['__NN_invoice_payments_amount']
                        . number_format($response['amount'], 2, ',', '') . ' ' . $currency . PHP_EOL;
        
        $paymentName     = ($response['invoice_type'] == 'PREPAYMENT') ? 'novalnet_prepayment' : 'novalnet_invoice';
        $referenceSuffix = array('BNR-' . $this->product . '-' . $orderNo, 'TID ' . $response['tid'] );
        $i = 1;

        $transComments .= $novalnetOrderLanguage['__NN_invoice_payments_multiple_reference_text']  . PHP_EOL;

        foreach ($referenceSuffix as $key => $val) {
            if (!empty($val)) {
                $suffix = (nnLoadLanguageIso('', $response['kSprache']) == 'ger' ) ? $i . '. ' :  ' ' .$i ; 
                $transComments .= sprintf($novalnetOrderLanguage['__NN_invoice_payments_reference'], $suffix) . ': ' . $referenceSuffix[$key] . PHP_EOL;
                $i+=1;
            }
        }
        return $transComments;
    }

    /**
     * Form cashpayment store details
     *
     * @param array  $response
     * @return string
     */
    public function prepareCashpaymentComments($response)
    {
        $response = array_map('utf8_decode', $response);

        $storeCount = 0;
        foreach ($response as $key => $value) {
            if (strpos($key, 'nearest_store_street') === 0) {
                $storeCount++;
            }
        }

        if ($storeCount == 0) {
            return '';
        }

        $novalnetOrderLanguage = nnLoadOrderLanguage($this->helper->oPlugin->kPlugin, $response['kSprache']);

        $duedate = new DateTime($response['cp_due_date']);
        $transComments  = ($response['cp_due_date'] != '')
                          ? $novalnetOrderLanguage['__NN_cashpayment_expiry_date'] . $duedate->format('d.m.Y') . PHP_EOL
                          : '';
        $transComments .= PHP_EOL . $novalnetOrderLanguage['__NN_cashpayment_nearest_store_details'] . PHP_EOL;

        for ($i = 1; $i <= $storeCount; $i++) {
            $transComments .= PHP_EOL . $response['nearest_store_title_' . $i];
            $transComments .= PHP_EOL . $response['nearest_store_street_' . $i];
            $transComments .= PHP_EOL . $response['nearest_store_city_' . $i];
            $transComments .= PHP_EOL . $response['nearest_store_zipcode_' . $i];
            $countryName = nnGetCountryName($response['nearest_store_country_' . $i]);
            $transComments .= PHP_EOL . (nnLoadLanguageIso($response['kSprache']) == 'ger'
                                ? $countryName->cDeutsch
                                : $countryName->cEnglisch). PHP_EOL;
        }

        return $transComments;
    }

    /**
     * Sets and assigns the fruad module values for second call
     *
     * @param string $payment
     * @param float  $orderAmount
     * @return none
     */
    public function setupFraudModuleValues($payment, $orderAmount)
    {
        global $shopUrl;
         // Condition to check whether the payment can be completed through fraud prevention setup
        if ($this->verifyFraudModule($payment) && !empty($_SESSION[$payment]['is_fraudcheck'])) {
            if (empty($_SESSION['request']['amount'])) {
                unset($_SESSION['nn_booking']['pin_by_callback']);
                unset($_SESSION['nn_booking']['pin_by_sms']);
            }

            $_SESSION['nn_amount']              = $orderAmount;
            $_SESSION['nn_' . $payment .'_tid'] = $_SESSION[$payment]['tid'];
            $_SESSION['nn_error']               = $this->helper->getConfigurationParams('pin_by_callback', $payment) == '2' ? $this->helper->oPlugin->oPluginSprachvariableAssoc_arr['__NN_sms_pin_message'] : $this->helper->oPlugin->oPluginSprachvariableAssoc_arr['__NN_callback_pin_message'];

            header('Location:' . $shopUrl . '/bestellvorgang.php?editZahlungsart=1');
            exit;
        }
    }

    /* Make XML call to server to perform the fraudmodule operation
     *
     * @param string  $payment
     * @return array
     */
    public function getPinStatus($payment)
    {
        $requestType = !empty($_SESSION[$payment]['nn_forgot_pin']) ? 'TRANSMIT_PIN_AGAIN' : 'PIN_STATUS';

        $fraudModuleParams  = '<?xml version="1.0" encoding="UTF-8"?><nnxml><info_request><vendor_id>' . $this->vendor
                              . '</vendor_id>';
        $fraudModuleParams .= '<vendor_authcode>' . $this->auth_code . '</vendor_authcode>';
        $fraudModuleParams .= '<request_type>' . $requestType . '</request_type>';
        $fraudModuleParams .= '<tid>' . $_SESSION[$payment]['tid'] . '</tid>';
        $fraudModuleParams .= '<remote_ip>' . nnGetIpAddress('REMOTE_ADDR') . '</remote_ip>';
        if ($requestType == 'PIN_STATUS') {
            $fraudModuleParams .= '<pin>' . $_SESSION[$payment]['nn_pin'] . '</pin>';
        }
        $fraudModuleParams .= '<lang>' . nnGetShopLanguage() . '</lang>';
        $fraudModuleParams .='</info_request></nnxml>';

        $transactionResponse = http_get_contents(
            $this->novalnetInfoportUrl,
            $this->getGatewayTimeout(),
            $fraudModuleParams
        );
        $response            = simplexml_load_string($transactionResponse);
        $response            = json_decode(json_encode($response), true);

        return $response;
    }

    /**
     * Performs postback call to novalnet server
     *
     * @param array   $response
     * @param string  $orderNo
     * @param integer $paymentKey
     * @param string  $orderComments
     * @param string  $paymentName
     * @return none
     */
    public function postBackCall($response, $orderNo, $paymentKey, $orderComments, $paymentName)
    {
        $postData = array(
            'vendor'    => $this->vendor,
            'product'   => $this->product,
            'tariff'    => $this->tariff,
            'auth_code' => $this->auth_code,
            'key'       => $paymentKey,
            'status'    => '100',
            'tid'       => !empty($response['tid']) ? $response['tid'] : $_SESSION[$paymentName]['tid'],
            'order_no'  => $orderNo
        );

        if (in_array($paymentKey, array('27', '41'))) {
            $postData['invoice_ref'] = 'BNR-' . $postData['product']  . '-' . $orderNo;
        }
        // Core function to handle cURL call with the transaction request
        http_get_contents($this->novalnetPaygateUrl, $this->getGatewayTimeout(), $postData);
    }

    /**
     * To insert the order details into Novalnet tables
     *
     * @param array   $response
     * @param integer $orderValue
     * @param integer $paymentKey
     * @param string  $paymentName
     * @return none
     */
    public function insertOrderIntoDB($response, $orderValue, $paymentKey, $paymentName)
    {
        global $DB;

        $order = new Bestellung($orderValue); // Loads the order object from shop

        $tid = !empty($response['tid']) ? $response['tid'] : $_SESSION[$paymentName]['tid'];

        $response['kSprache'] = $order->kSprache;

        $customerObj = new Kunde($order->kKunde);
        $insertOrder = new stdClass();
        $insertOrder->cNnorderid           = $order->cBestellNr;
        $insertOrder->cKonfigurations      = serialize(array(
                                                            'vendor' => $this->vendor,
                                                            'auth_code' => $this->auth_code,
                                                            'product' => $this->product,
                                                            'tariff' => $this->tariff,
                                                            'key' => $paymentKey));
        $insertOrder->nNntid               = $tid;
        $insertOrder->cZahlungsmethode     = !empty($response['nn_payment']) ? $response['nn_payment'] : $paymentName;
        $insertOrder->cMail                = $customerObj->cMail;
        $insertOrder->nStatuswert          = !empty($response['tid_status']) ? $response['tid_status'] : $_SESSION[$paymentName]['status'];
        $insertOrder->nBetrag              = !is_numeric($response['amount']) ? $this->helper->generateDecode($response['amount'], $response['uniqid']) : ((!empty($response['amount']) ? $response['amount'] : $_SESSION['nn_amount']) * 100);
        $insertOrder->bOnetimeshopping     = !empty($_SESSION[$paymentName]['one_click_shopping']) ? $_SESSION[$paymentName]['one_click_shopping'] : 0;
        $insertOrder->cZeroBookingParams   = !empty($_SESSION['nn_booking']) ? serialize($_SESSION['nn_booking']) : '';
        $insertOrder->cAdditionalInfo      = $this->setupAdditionalInfoData($response, $paymentKey, $insertOrder->bOnetimeshopping, $paymentName);

        $DB->insertRow('xplugin_novalnetag_tnovalnet_status', $insertOrder);

        if (!in_array($paymentKey, array('27', '78', '59')) && $response['status'] == '100') {                    // If the payment server response is successful
            $insertCallback = new stdClass();
            $insertCallback->cBestellnummer  = $insertOrder->cNnorderid;
            $insertCallback->dDatum          = date('Y-m-d H:i:s');
            $insertCallback->cZahlungsart    = !empty($_SESSION['nn_request']['payment_type']) ? $_SESSION['nn_request']['payment_type'] : $response['payment_type'];
            $insertCallback->nReferenzTid    = $tid;
            $insertCallback->nCallbackAmount = $insertOrder->nBetrag;
            $insertCallback->cWaehrung       = isset($response['currency']) ? $response['currency'] : $_SESSION[$paymentName]['currency'];

            $DB->insertRow('xplugin_novalnetag_tcallback', $insertCallback);
        }

        if (!empty($_SESSION['nn_aff_id'])) { // If the order is an affiliate order
            $insertAffiliate = new stdClass();
            $insertAffiliate->nAffId      = $this->vendor;
            $insertAffiliate->cCustomerId = $order->kKunde;
            $insertAffiliate->nAffOrderNo = $insertOrder->cNnorderid;

            $DB->insertRow('xplugin_novalnetag_taff_user_detail', $insertAffiliate);
        }
    }

    /**
     * Stores the additional information related to the particular payment method
     *
     * @param array   $response
     * @param integer $paymentKey
     * @param integer $referenceOrder
     * @param string  $paymentName
     * @return array
     */
  
    public function setupAdditionalInfoData($response, $paymentKey, $referenceOrder, $paymentName)
    {
        if (!in_array($paymentKey, array('27', '41')) && ($paymentKey != '59'
            && ($this->helper->getConfigurationParams('extensive_option', $paymentName) != '1'
            || ($referenceOrder&&!empty($_SESSION[$paymentName]['nn_save_payment'])) || empty($_SESSION['Kunde']->kKunde)))) {
            return '';
        }

        if ($_SESSION[$paymentName]['nn_save_payment']) {
            switch ($paymentKey) {
                case '6':
                    if ($this->helper->getConfigurationParams('cc3d_active_mode') ||
                            $this->helper->getConfigurationParams('cc3d_fraud_check')) {
                        return '';
                    }

                    return serialize(array(
                        'referenceOption1' => $response['cc_card_type'],
                        'referenceOption2' => utf8_decode($response['cc_holder']),
                        'referenceOption3' => $response['cc_no'],
                        'referenceOption4' => $response['cc_exp_month'] .'/'. $response['cc_exp_year']
                    ));

                case '34':
                    return serialize(array(
                        'referenceOption1' => $response['paypal_transaction_id'] != ''
                                                ? $response['paypal_transaction_id']
                                                : '',
                        'referenceOption2' => $response['tid']
                    ));

                case '37':
                case '40':
                    return serialize(array(
                        'referenceOption1' => utf8_decode($response['bankaccount_holder']),
                        'referenceOption2' => $response['iban']
                    ));
                case '59':
                    $cashpaymentComments  = $this->prepareCashpaymentComments($response);
                    return serialize(array(
                        'cashpaymentSlipduedate'  => $response['cp_due_date'],
                        'cashpaymentStoreDetails' => $cashpaymentComments,
                    ));
                case '27':
                case '41':
                    return serialize(array(
                        'invoice_account_holder'  => $response['invoice_account_holder']
                    ));
                default:
                    return '';
            }
        }
    }

    /**
     * To insert the order details into Novalnet table for failure
     *
     * @param array        $response
     * @param object       $order
     * @param string       $paymentName
     * @param integer|null $paymentKey
     * @return bool
     */
    public function insertOrderIntoDBForFailure($response, $order, $paymentName, $paymentKey = '')
    {
        global $DB;

        $insertOrder = new stdClass();
        $insertOrder->cNnorderid        = $order->cBestellNr;
        $insertOrder->cKonfigurations   = serialize(array(
                                                        'vendor' => $this->vendor,
                                                        'auth_code' => $this->auth_code,
                                                        'product' => $this->product,
                                                        'tariff' => $this->tariff,
                                                        'key' => $paymentKey));
        $insertOrder->nNntid            = $response['tid'];
        $insertOrder->cZahlungsmethode  = !empty($paymentName) ? $paymentName : $response['payment'];
        $insertOrder->cMail             = $response['email'];
        $insertOrder->nStatuswert       = !empty($response['tid_status']) ? $response['tid_status'] : $response['status'];
        $insertOrder->nBetrag           = gibPreisString($order->fGesamtsumme) * 100;

        $DB->insertRow('xplugin_novalnetag_tnovalnet_status', $insertOrder);
    }

    /**
     * Sets and displays payment error
     *
     * @param none
     * @return none
     */
    public function assignPaymentError()
    {
        global $cHinweis, $hinweis;

        $error = isset($_SESSION['nn_error'])
                    ? $_SESSION['nn_error']
                    : (isset($_SESSION['fraud_check_error']) ? $_SESSION['fraud_check_error'] : '');

        if (!empty($error)) {
            if (isset($hinweis)) {
                $hinweis = $error;
            } elseif (isset($cHinweis)) {
                $cHinweis = $error;
            }
            // Unsets the session error if there is any
            unset($_SESSION['nn_error'], $_SESSION['fraud_check_error']);
        }
    }

    /**
     * Retrieves response status texts from response
     *
     * @param array $response
     * @return string
     */
    public function getResponseText($response)
    {
        return (utf8_decode(!empty($response['status_desc']) ? $response['status_desc'] : (!empty($response['status_text']) ? $response['status_text'] : (!empty($response['status_message']) ? $response['status_message'] : $this->helper->oPlugin->oPluginSprachvariableAssoc_arr['__NN_transaction_error'])))); //response status texts from response
    }

    /**
     * Execute database update operation
     *
     * @param string $table
     * @param string $fields
     * @param string $value
     * @return none
     */
    public static function performDbExecution($table, $fields, $value)
    {
        global $DB, $shopQuery;

        $DB->$shopQuery('UPDATE ' . $table . ' SET ' . $fields . ' WHERE ' . $value, 4);
    }

    /**
     * Get gateway timeout limit
     *
     * @param none
     * @return integer
     */
    public function getGatewayTimeout()
    {
        $gatewayTimeout = $this->helper->getConfigurationParams('gateway_timeout');

        return (nnIsDigits($gatewayTimeout) ? $gatewayTimeout : 240);
    }

    /**
     * Get currency type for the current order
     *
     * @param string $orderNo
     * @return string
     */
    public static function getPaymentCurrency($orderNo)
    {
        global $DB, $shopQuery;

        $currency = $DB->$shopQuery('SELECT twaehrung.cISO FROM twaehrung
        LEFT JOIN tbestellung ON twaehrung.kWaehrung = tbestellung.kWaehrung WHERE cBestellNr ="' . $orderNo . '"', 1);

        return $currency->cISO;
    }

    /**
     * Check to confirm guarantee payment option execution
     *
     * @param string $payment
     * @return bool
     */
    public function checkGuaranteedPaymentOption($payment)
    {
        if ($this->helper->getConfigurationParams('guarantee', $payment) !== '0') {
            $shippingAddress = array(
                                    $_SESSION['Lieferadresse']->cStrasse,
                                    $_SESSION['Lieferadresse']->cHausnummer,
                                    $_SESSION['Lieferadresse']->cPLZ,
                                    $_SESSION['Lieferadresse']->cOrt
                                );

            $customerAddress = array(
                                    $_SESSION['Kunde']->cStrasse,
                                    $_SESSION['Kunde']->cHausnummer,
                                    $_SESSION['Kunde']->cPLZ,
                                    $_SESSION['Kunde']->cOrt
                                );

            $orderAmount = $_SESSION['Warenkorb']->gibGesamtsummeWaren(array(C_WARENKORBPOS_TYP_ARTIKEL), true) * 100;

            $guaranteeAmount = $this->helper->getConfigurationParams('guarantee_min_amount', $payment) != '' ? $this->helper->getConfigurationParams('guarantee_min_amount', $payment) : 999;
            if (in_array($_SESSION['Kunde']->cLand, array('DE','AT','CH')) && $_SESSION['Waehrung']->cISO == 'EUR' && $orderAmount > $guaranteeAmount && $customerAddress == $shippingAddress) { // Condition to check whether payment guarantee option can be processed
                $_SESSION['nn_' . $payment . '_guarantee'] = true;

                if (isset($_SESSION['nn_' . $payment . '_guarantee_error'])) {
                    unset($_SESSION['nn_' . $payment . '_guarantee_error']);
                }
            } elseif ($this->helper->getConfigurationParams('guarantee_force', $payment) !== '0') {
                if (isset($_SESSION['nn_' . $payment . '_guarantee'])) {
                    unset($_SESSION['nn_' . $payment . '_guarantee']);
                }
            } else {
                if(!in_array($_SESSION['Kunde']->cLand, array('DE','AT','CH'))) {
                  $_SESSION['nn_' . $payment . '_guarantee_msg'] = $this->helper->oPlugin->oPluginSprachvariableAssoc_arr['__NN_guarantee_country_error'];
                } elseif ($_SESSION['Waehrung']->cISO != 'EUR') {
                  $_SESSION['nn_' . $payment . '_guarantee_msg'] = $this->helper->oPlugin->oPluginSprachvariableAssoc_arr['__NN_guarantee_currency_error'];
                } elseif ($orderAmount < $guaranteeAmount) {
                  $_SESSION['nn_' . $payment . '_guarantee_msg'] = sprintf(
                                $this->helper->oPlugin->oPluginSprachvariableAssoc_arr['__NN_guarantee_min_amount_error'],
                                $guaranteeAmount/100,
                                $_SESSION['Waehrung']->cISO
                            );
                } elseif($customerAddress != $shippingAddress) {
                  $_SESSION['nn_' . $payment . '_guarantee_msg'] = $this->helper->oPlugin->oPluginSprachvariableAssoc_arr['__NN_guarantee_address_error'];
                }
                $_SESSION['nn_' . $payment . '_guarantee']       = true;
                $_SESSION['nn_' . $payment . '_guarantee_error'] = true;
            }
        } else {
            if (isset($_SESSION['nn_' . $payment . '_guarantee'])) {
                unset($_SESSION['nn_' . $payment . '_guarantee']);
            }
        }
    }

    /******************** Validation process **************************/

    /**
     * Check to validate basic parameters configured
     *
     * @param none
     * @return bool
     */
    public function isConfigInvalid()
    {
        return ($this->helper->getConfigurationParams('novalnet_public_key') == '' || empty($this->tariff));
    }

    /**
     * Validates mandatory configuration parameters and customer parameters before requesting to the payment server
     *
     * @param array  $paymentRequestParameters
     * @param object $order
     * @param string $paymentName
     * @return bool
     */
    public function preValidationCheckOnSubmission($paymentRequestParameters, $order, $paymentName)
    {
        if ($_SESSION['Zahlungsart']->nWaehrendBestellung == 0) {
            $_SESSION['nn_during_order'] = true;
        }

        if ((empty($paymentRequestParameters['first_name']) && empty($paymentRequestParameters['last_name'])) || !valid_email($paymentRequestParameters['email'])) { // Validates the server customer mandatory parameters and sets the error if not configured properly
            $_SESSION['nn_error'] = $this->helper->oPlugin->oPluginSprachvariableAssoc_arr['__NN_customer_details_error'];
        }

        if (!empty($_SESSION['nn_error'])) {
            // Redirects to the error page
            $this->redirectOnError($order, $paymentRequestParameters, $paymentName);
        }
    }

    /**
     * Validation for parameters on form payments
     *
     * @param string $paymentType
     * @return bool
     */
    public function basicValidationOnhandleAdditional($paymentType)
    {
        global $smarty;

        if (!empty($_SESSION['nn_' . $paymentType . '_guarantee']) &&
            !empty($_SESSION['nn_' . $paymentType . '_guarantee_error'])) {
            $_SESSION['nn_error'] = $_SESSION['nn_' . $paymentType . '_guarantee_msg'];
        } elseif (!empty($_POST['nn_dob'])) { // Condition to check if the birthdate is eligible for guarantee payment
            if (time() < strtotime('+18 years', strtotime($_POST['nn_dob']))) {
                if (isset($_SESSION['nn_' . $paymentType . '_guarantee'])) {
                    unset($_SESSION['nn_' . $paymentType . '_guarantee']);
                }
                if ($this->helper->getConfigurationParams('guarantee_force', $paymentType) == '0') {
                    $_SESSION['nn_error'] = $this->helper->oPlugin->oPluginSprachvariableAssoc_arr['__NN_age_limit_error'];
                }
            }
        } elseif (!empty($_SESSION['nn_' . $paymentType . '_guarantee']) && empty($_POST['nn_dob'])) {
            if (isset($_SESSION['nn_' . $paymentType . '_guarantee'])) {
                unset($_SESSION['nn_' . $paymentType . '_guarantee']);
            }
        }

        if (!empty($_SESSION['nn_error'])) {
            $smarty->assign('nnValidationError', $_SESSION['nn_error']);
            return false;
        }

        return true;
    }

    /**
     * To check whether fraud prevention can be handled or not
     *
     * @param string $payment
     * @return bool
     */
    public function verifyFraudModule($payment)
    {
        $pinAmount = $this->helper->getConfigurationParams('pin_amount', $payment);

        return !(($pinAmount && $pinAmount > 0
        && (($_SESSION['Warenkorb']->gibGesamtsummeWaren(array(C_WARENKORBPOS_TYP_ARTIKEL), true) * 100) < $pinAmount))
        || (!in_array($_SESSION['Kunde']->cLand, array('DE','AT','CH')))
        || $_SESSION['Zahlungsart']->nWaehrendBestellung == 0
        || !empty($_SESSION['nn_' . $payment . '_guarantee']));
    }

    /**
     * Check when order amount has been changed after first payment call
     *
     * @param integer $currentOrderAmount
     * @param string  $payment
     * @return none
     */
    public function orderAmountCheck($currentOrderAmount, $payment)
    {
        global $shopUrl;
        // Condition to verify the order amount with the amount sent during first payment call
        if ($currentOrderAmount != $_SESSION['nn_amount']) {
            $_SESSION['fraud_check_error'] = $this->helper->oPlugin->oPluginSprachvariableAssoc_arr['__NN_amount_fraudmodule_error'];

            $this->helper->novalnetSessionCleanUp($payment); // Unset the entire novalnet session on order completion

            header('Location:' . $shopUrl . '/bestellvorgang.php?editZahlungsart=1');
            exit;
        }
    }

    /**
     * Check and updates shop information text that will be displayed in the paymet page
     *
     * @param string $paymentModuleId
     * @param string $paymentName
     * @return none
     */
    public function setupShopInformationText($paymentModuleId, $paymentName)
    {
        if (JTL_VERSION >= 406 && $paymentModuleId) {
            global $DB, $shopQuery;

            $languageKey  = $_SESSION['cISOSprache'];
            $paymentId    = nnGetShopPaymentId($paymentModuleId);
            $shopInfoText = $DB->$shopQuery('SELECT cHinweisTextShop FROM tzahlungsartsprache WHERE kZahlungsart = ' . $paymentId . ' and cISOSprache = "' . $languageKey .'"', 1);

            if (empty($shopInfoText->cHinweisTextShop)) {
                $paymentInfoText = $this->helper->oPlugin->oPluginSprachvariableAssoc_arr['__NN_payment_method_name_' . $paymentName];

                if (!empty($paymentInfoText)) {
                    $DB->$shopQuery('UPDATE tzahlungsartsprache SET cHinweisTextShop ="' . $paymentInfoText . '" WHERE cISOSprache ="' . $languageKey .'" AND kZahlungsart = ' . $paymentId, 4);
                }
            }
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
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}
