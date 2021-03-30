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
     * @var Plugin
     */
    public $oPlugin;

    /**
     * @var null|instance
     */
    static $_instance = null;

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
    public $novalnetVersion = '11.1.0';

    /**
     * @var string
     */
    public $novalnetPaygateUrl = 'https://payport.novalnet.de/paygate.jsp';

    /**
     * @var string
     */
    public $novalnetInfoportUrl = 'https://payport.novalnet.de/nn_infoport.xml';

    /**
     * Constructor
     */
    public function __construct()
    {
        // Creates instance for the NovalnetHelper class
        $this->helper = new NovalnetHelper();

        // Retrieves Novalnet plugin object
        $this->oPlugin = nnGetPluginObject();

        // Assigns error message to the shop's variable $hinweis
        $this->assignPaymentError();

        // Gets tariff values to extract tariff id and tariff type
        $this->tariffValues = $this->helper->getConfigurationParams('tariffid');
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
        return ((in_array($payment, array('novalnet_cc', 'novalnet_sepa', 'novalnet_invoice', 'novalnet_paypal'))) &&nnIsDigits($this->helper->getConfigurationParams('manual_check_limit')) && $amount >= $this->helper->getConfigurationParams('manual_check_limit'));
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

        $affDetails = $this->helper->getAffiliateDetails(); // Get affiliate details for the current user

        $orderAmount = gibPreisString($order->fGesamtsumme) * 100; // Form order amount in cents with conversion using shop function

        $amount = (($this->helper->getConfigurationParams('extensive_option', $payment) == '2' && $this->tariffValues[1] == 2) && empty($_SESSION['nn_' . $payment . '_guarantee'])) ? 0 : $orderAmount; // Assigns amount based on zero-amount enable option

        /******** Basic parameters *****/

        $paymentRequestParameters = array(
            'vendor'           => empty($affDetails->vendorid) ? $this->helper->getConfigurationParams( 'vendorid' ) : $affDetails->vendorid,
            'auth_code'        => empty($affDetails->cAffAuthcode) ? $this->helper->getConfigurationParams( 'authcode' ) : $affDetails->cAffAuthcode,
            'product'          => $this->helper->getConfigurationParams('productid'),
            'tariff'           => $this->tariffValues[0],
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
            'tel'              => !empty($_SESSION[$payment]['nn_tel_number']) ? $_SESSION[$payment]['nn_tel_number'] : $_SESSION['Kunde']->cTel,
            'mobile'           => !empty($_SESSION[$payment]['nn_mob_number']) ? $_SESSION[$payment]['nn_mob_number'] : $_SESSION['Kunde']->cTel,
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

        /******** Additional parameters *****/

        if ($this->doManualLimitCheck($orderAmount, $payment)) { // Manual limit check for the order
            $paymentRequestParameters['on_hold'] = 1;
        }

        $referrerId = $this->helper->getConfigurationParams('referrerid');

        if (nnIsDigits($referrerId)) { // Condition to check if the Referrer ID is an integer
            $paymentRequestParameters['referrer_id'] = $referrerId;
        }

        $txnReference1 = $this->helper->getConfigurationParams('reference1', $payment);

        if ($txnReference1 != '') { // Condition to check if the transaction reference 1 is valid
            $paymentRequestParameters['input1']     = 'reference1';
            $paymentRequestParameters['inputval1']  = strip_tags($txnReference1);
        }

        $txnReference2 = $this->helper->getConfigurationParams('reference1', $payment);

        if ($txnReference2 != '') { // Condition to check if the transaction reference 2 is valid
            $paymentRequestParameters['input2']     = 'reference2';
            $paymentRequestParameters['inputval2']  = strip_tags($txnReference2);
        }

        if ($this->tariffValues[1] != 2) { // If the tariff ID configured is a subscription tariff id

            $tariffPeriod = $this->helper->getConfigurationParams('tariff_period');

            if ($tariffPeriod != '') { // Condition to check if the tariff period can be passed to server
                $paymentRequestParameters['tariff_period'] = $tariffPeriod;
            }

            $tariffPeriod2 = $this->helper->getConfigurationParams('tariff_period2');
            $tariffPeriodAmount = $this->helper->getConfigurationParams('tariff_period2_amount');

            if ($tariffPeriodAmount != '' && $tariffPeriod2 != '') { // Condition to check if the tariff period2 values can be passed to server
                $paymentRequestParameters['tariff_period2'] = $tariffPeriod2;
                $paymentRequestParameters['tariff_period2_amount'] = $tariffPeriodAmount;
            }
        }

        if ($_SESSION['Zahlungsart']->nWaehrendBestellung == 0) { // Send order number with the payment request for order complete process
            $paymentRequestParameters['order_no'] = $order->cBestellNr;
        }

        if ($this->verifyFraudModule($payment) && empty($_SESSION[$payment]['one_click_shopping'])) { // Condition to check if fraud module can be handled for the payment
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

        $this->orderAmountCheck($order->fGesamtsumme, $payment); // Check when order amount has been changed after first payment call

        $aryResponse = $this->getPinStatus($payment); // Do transaction XML call for fraud module

        if (isset($aryResponse['status']) && $aryResponse['status'] == 100) { // Return on successful response
            $_SESSION[$payment]['tid_status'] = $aryResponse['tid_status'];
            return true;
        } else { // Perform error response operations
            unset($_SESSION[$payment]['nn_forgot_pin']);

            if ($aryResponse['status'] == '0529006') {
                $_SESSION[$payment . '_invalid']    = TRUE;
                $_SESSION[$payment . '_time_limit'] = time()+(30*60);
                unset($_SESSION['nn_' . $payment . '_tid']);
            }

            $_SESSION['nn_error'] = utf8_decode(!empty($aryResponse['status_message']) ? $aryResponse['status_message'] : (!empty($aryResponse['pin_status']['status_message']) ? $aryResponse['pin_status']['status_message'] : ''));

            header('Location:' . $shopUrl . '/bestellvorgang.php?editZahlungsart=1');
            exit;
        }
    }

    /**
     * Compare the hash generated for redirection payments
     *
     * @param array $response
     * @param array $order
     * @return none
     */
    public function hashCheckForRedirects($response, $order)
    {
        if ($response['hash2'] != $this->helper->generateHashValue($response)) { // Condition to check whether the payment is redirect
            $_SESSION['nn_error'] = html_entity_decode($this->oPlugin->oPluginSprachvariableAssoc_arr['__NN_hash_error']);

            $this->redirectOnError($order, $response); // Redirects to the error page
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

        if ($this->helper->getConfigurationParams('pin_by_callback', $payment) == '0' || !$this->verifyFraudModule($payment)) {
            return true;
        } else {
            if (isset($_SESSION['nn_' . $payment . '_tid'])) { // If fraud module option is enabled
                $smarty->assign('pin_enabled', $payment);
            } else {
                if ($this->helper->getConfigurationParams('pin_by_callback', $payment) == '1') {// If pin by callback enabled
                    $smarty->assign('pin_by_callback', $payment);
                } elseif ($this->helper->getConfigurationParams('pin_by_callback', $payment) == '2') { // If pin by sms enabled
                    $smarty->assign('pin_by_sms', $payment);
                }
            }
        }
    }

    /**
     * Make CURL payment request to Novalnet server
     *
     * @param none
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
     * @return none
     */
    public function redirectOnError($order, $response)
    {
        global $shopUrl, $shopVersion;

        if ($_SESSION['Zahlungsart']->nWaehrendBestellung == 0 || !empty($_SESSION['nn_during_order'])) {

            $transactionComments = $this->formTransactionComments($response, $order, true); // Build the Novalnet order comments

            self::performDbExecution('tbestellung', 'cKommentar = "' . $transactionComments . '", cStatus = ' . BESTELLUNG_STATUS_STORNO . ', cAbgeholt="Y"', 'kBestellung ="' . $order->kBestellung . '"');

            $jtlPaymentmethod = PaymentMethod::create($order->Zahlungsart->cModulId); // Instance of class PaymentMethod
            $jtlPaymentmethod->sendMail($order->kBestellung, MAILTEMPLATE_BESTELLUNG_STORNO); // Triggers cancellation mail template

            // Clears shop session based on shop version
            if ($shopVersion == '4x') {
                $session = Session::getInstance(true,true);
                $session->cleanUp();
            } else {
                raeumeSessionAufNachBestellung();
            }

            $this->helper->novalnetSessionCleanUp($response['inputval3']); // Unsets the entire novalnet session on order completion
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

            self::performDbExecution('tbestellung', 'cKommentar = CONCAT(cKommentar, "' . PHP_EOL . $this->oPlugin->oPluginSprachvariableAssoc_arr['__NN_order_not_sucessful'] . '"), cStatus = ' . BESTELLUNG_STATUS_STORNO . ', cAbgeholt="Y"', 'kBestellung ="' . $order->kBestellung . '"');

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
     * @return none
     */
    public function handlePaymentCompletion($order, $sessionHash, $paymentKey, $paymentName, $response = array())
    {
        global $shopUrl, $DB, $shopQuery;

        if (empty($response)) {
            $response = $_SESSION[$paymentName];
        }

        if (in_array($response['status'], array(90, 100))) { // On successful payment server response

            if (!empty($_SESSION['nn_during_order'])) {

                if (!empty($response['hash2'])) { // Compares the hash generated for redirection payments
                    $this->hashCheckForRedirects($response, $order);
                }

                $transactionComments = $this->formTransactionComments($response, $order); // Form transaction comments for the current order
                self::performDbExecution('tbestellung', 'cKommentar = CONCAT(cKommentar, "' . $transactionComments . '")', 'kBestellung =' . $order->kBestellung); // Updates the value into the database

                $jtlPaymentmethod = PaymentMethod::create($order->Zahlungsart->cModulId); // Retrieves payment object from class PaymentMethod

                $jtlPaymentmethod->sendMail($order->kBestellung, MAILTEMPLATE_BESTELLUNG_AKTUALISIERT);// Triggers order updation mail template
            } else {
                // Post back acknowledgement call to map the order into Novalnet server
                $this->postBackCall($response, $order->cBestellNr, $paymentKey, $order->cKommentar);
            }

            $this->insertOrderIntoDB($response, $order->kBestellung, $paymentKey); // Logs the order details in Novalnet tables for success

            $this->getPaymentTestmode($response); // Returns test mode value based on the payment type

            if ($this->oPlugin->oPluginEmailvorlageAssoc_arr['novalnetnotification']->cAktiv == 'Y' && $this->helper->getConfigurationParams('testmode', $paymentName) == '0' && $response['test_mode'] == '1') {

                require_once PFAD_ROOT . PFAD_INCLUDES . 'mailTools.php';

                $adminDetails = $DB->$shopQuery('SELECT cName, cMail from tadminlogin LIMIT 1',1);

                $oMail                     = new stdClass();
                $oMail->tkunde             = $_SESSION['Kunde'];
                $oMail->tkunde->cBestellNr = $order->cBestellNr;
                $oMail->tkunde->kSprache   = gibStandardsprache(true)->kSprache;
                $oMail->mail->toName       = $adminDetails->cName;

                if (!empty($adminDetails->cMail)) {
                    $oMail->mail->toEmail  = $adminDetails->cMail;
                }

                sendeMail('kPlugin_' . $this->oPlugin->kPlugin . '_novalnetnotification', $oMail); // Triggers email notification to merchant when the order placed as test order
            }

            $this->helper->novalnetSessionCleanUp($paymentName); // Unset the entire novalnet session on order completion

            header( 'Location: ' . $shopUrl . '/bestellabschluss.php?i=' . $sessionHash);
            exit;

        } else {

            $_SESSION['nn_error'] = $this->getResponseText($response);

            $this->insertOrderIntoDBForFailure($response, $order, $paymentKey); // Logs the order details in Novalnet tables for failure
            $this->redirectOnError($order, $response); // Returns with error message on error
        }
    }

    /**
     * Finalize the order
     *
     * @param array  $order
     * @param array  $payment
     * @param array  $paymentKey
     * @param array  $response
     * @return bool
     */
    public function verifyNotification($order, $payment, $paymentKey, $response = array())
    {
        if (empty($response)) {
            $response = $_SESSION[$payment];
        }

        if (in_array($response['status'], array(90, 100))) { // On successful payment server response

            if (!empty($response['hash2'])) {
                $this->hashCheckForRedirects($response, $order); // Compares the hash generated for redirection payments
            }

            if (empty($_SESSION['nn_during_order'])) { // Check whether the order is placed when during order process and assign the transaction comments
                $_POST['kommentar'] = $this->formTransactionComments($response, $order); // Form transaction comments for the current order
                unset($_SESSION['kommentar']);
            }

            return true;

        } else { // Assign error test on payment server response

            $_SESSION['nn_error'] = $this->getResponseText($response); // Retrieves response status texts from response

            if (!empty($_SESSION['nn_during_order'])) {
                $this->insertOrderIntoDBForFailure($response, $order, $paymentKey); // Logs the order details in Novalnet tables for failure
            }

            $this->redirectOnError($order, $response); // Returns with error message on error

            return false;
        }
    }

    /**
     * Build the Novalnet order comments
     *
     * @param array $response
     * @param array $order
     * @return string
     */
    public function formTransactionComments($response, $order, $onError = false)
    {
        $transactionComments = !empty($_SESSION['kommentar']) ? $_SESSION['kommentar'] . PHP_EOL . PHP_EOL . $order->cZahlungsartName . PHP_EOL : $order->cZahlungsartName . PHP_EOL;

        $this->getPaymentTestmode($response); // Returns test mode value based on the payment type

        if (!empty($response['test_mode']) || $this->helper->getConfigurationParams('testmode', $response['inputval3']) != '0') { // Condition to retrieve the testmode for the payment
            $transactionComments .= $this->oPlugin->oPluginSprachvariableAssoc_arr['__NN_test_order'] . PHP_EOL;
        }

        if (!empty($response['tid'])) {
            $transactionComments .= $this->oPlugin->oPluginSprachvariableAssoc_arr['__NN_tid_label'] . $response['tid'] . PHP_EOL;
        }

        $transactionComments .= !empty($_SESSION['nn_error']) ? $_SESSION['nn_error'] : ($response['status'] != 100 ? $this->getResponseText($response) . PHP_EOL : '');

        if (in_array($response['inputval3'], array('novalnet_invoice', 'novalnet_prepayment')) && !$onError) {
            $response['kSprache']    = $order->kSprache;
            $invoicePaymentsComments = $this->formInvoicePrepaymentComments($response, $order->Waehrung->cISO);
            $transactionComments    .= $invoicePaymentsComments;
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
        if (!nnIsDigits($response['test_mode'])) {
            $response['test_mode'] = $this->helper->generateDecode($response['test_mode']);
        }
    }

    /**
     * Form invoice & prepayment payments comments
     *
     * @param array  $datas
     * @param string $currency
     * @param bool   $updateAmount
     * @return string
     */
    public function formInvoicePrepaymentComments($datas, $currency, $updateAmount = false)
    {
        $datas = array_map('utf8_decode', $datas);

        // Retrieves the language variables based on the end-user's order language
        $novalnetOrderLanguage = nnLoadOrderLanguage($this->oPlugin->kPlugin, $datas['kSprache']);

        $orderNo = !empty($datas['order_no']) ? $datas['order_no'] : 'NN_ORDER';
        $duedate = new DateTime($datas['due_date']);
        $transComments  = PHP_EOL . $novalnetOrderLanguage['__NN_invoice_payments_comments'] . PHP_EOL;
        $transComments .= $novalnetOrderLanguage['__NN_invoice_duedate'] . $duedate->format('d.m.Y') . PHP_EOL;
        $transComments .= $novalnetOrderLanguage['__NN_invoice_payments_holder'] . PHP_EOL;
        $transComments .= 'IBAN: ' . $datas['invoice_iban'] . PHP_EOL;
        $transComments .= 'BIC:  ' . $datas['invoice_bic'] . PHP_EOL;
        $transComments .= 'Bank: ' . $datas['invoice_bankname'] . ' ' . $datas['invoice_bankplace'] . PHP_EOL;
        $transComments .= $novalnetOrderLanguage['__NN_invoice_payments_amount'] . number_format($datas['amount'], 2, ',', '') . ' ' . $currency . PHP_EOL;
        $referenceParams = $updateAmount ? unserialize($datas['referenceValues']) : $this->helper->getInvoicePaymentsReferences($datas['inputval3']);
        $refCount = array_count_values($referenceParams);
        $referenceSuffix = array('BNR-' . $this->helper->getConfigurationParams('productid') . '-' . $orderNo, 'TID ' . $datas['tid'], $novalnetOrderLanguage['__NN_order_number_text'] . $orderNo );
        $i = 1;

        $transComments .= (($refCount['1'] > 1) ? $novalnetOrderLanguage['__NN_invoice_payments_multiple_reference_text'] : $novalnetOrderLanguage['__NN_invoice_payments_single_reference_text']) . PHP_EOL;

        foreach ($referenceParams as $key => $val) {
            if (!empty($val)) {
                $suffix = (nnLoadLanguageIso($datas['kSprache']) == 'ger' && $refCount['1'] > 1) ? $i . '. ' : ($refCount['1'] > 1 ? ' ' .$i : '');
                $transComments .= sprintf($novalnetOrderLanguage['__NN_invoice_payments_reference'], $suffix) . ': ' . $referenceSuffix[$key] . PHP_EOL;
                $i+=1;
            }
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

        if ($this->verifyFraudModule($payment) && !empty($_SESSION[$payment]['is_fraudcheck'])) { // Condition to check whether the payment can be completed through fraud prevention setup

            if (empty($_SESSION['request']['amount'])) {
                unset($_SESSION['nn_booking']['pin_by_callback']);
                unset($_SESSION['nn_booking']['pin_by_sms']);
            }

            $_SESSION['nn_amount'] = $orderAmount;
            $_SESSION['nn_' . $payment .'_tid'] = $_SESSION[$payment]['tid'];
            $_SESSION['nn_error'] = $this->helper->getConfigurationParams('pin_by_callback', $payment) == '2' ? $this->oPlugin->oPluginSprachvariableAssoc_arr['__NN_sms_pin_message'] : $this->oPlugin->oPluginSprachvariableAssoc_arr['__NN_callback_pin_message'];

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
        $vendorId = $this->helper->getConfigurationParams('vendorid');
        $authcode = $this->helper->getConfigurationParams('authcode');

        if (!empty($_SESSION['nn_aff_id'])) {
            $affDetails = $this->helper->getAffiliateDetails(); // Get affiliate details for the current user
            $vendorId = $affDetails->vendorid;
            $authcode = $affDetails->cAffAuthcode;
        }
        $requestType = !empty($_SESSION[$payment]['nn_forgot_pin']) ? 'TRANSMIT_PIN_AGAIN' : 'PIN_STATUS';

        $fraudModuleParams  = '<?xml version="1.0" encoding="UTF-8"?><nnxml><info_request><vendor_id>' . $vendorId . '</vendor_id>';
        $fraudModuleParams .= '<vendor_authcode>' . $authcode . '</vendor_authcode>';
        $fraudModuleParams .= '<request_type>' . $requestType . '</request_type>';
        $fraudModuleParams .= '<tid>' . $_SESSION[$payment]['tid'] . '</tid>';
        if ($requestType == 'PIN_STATUS')
            $fraudModuleParams .= '<pin>' . $_SESSION[$payment]['nn_pin'] . '</pin>';
        $fraudModuleParams .= '<lang>' . nnGetShopLanguage() . '</lang>';
        $fraudModuleParams .='</info_request></nnxml>';

        $transactionResponse = http_get_contents($this->novalnetInfoportUrl, $this->getGatewayTimeout(), $fraudModuleParams);
        $response = simplexml_load_string($transactionResponse);
        $response = json_decode(json_encode($response), true);

        return $response;
    }

    /**
     * Performs postback call to novalnet server
     *
     * @param array   $response
     * @param string  $orderNo
     * @param integer $paymentKey
     * @param string  $orderComments
     * @return none
     */
    public function postBackCall($response, $orderNo, $paymentKey, $orderComments)
    {
        $vendorId = $this->helper->getConfigurationParams('vendorid');
        $authcode = $this->helper->getConfigurationParams('authcode');

        $affiliateDetails = $this->helper->getAffiliateDetails(); // Get affiliate details for the current user

        if (!empty($affiliateDetails)) {
            $vendorId = $affiliateDetails->vendorid;
            $authcode = $affiliateDetails->cAffAuthcode;
        }

        $postData = array(
            'vendor'    => $vendorId,
            'product'   => $this->helper->getConfigurationParams('productid'),
            'tariff'    => $this->tariffValues[0],
            'auth_code' => $authcode,
            'key'       => $paymentKey,
            'status'    => 100,
            'tid'       => !empty($response['tid']) ? $response['tid'] : $_SESSION[$response['inputval3']]['tid'],
            'order_no'  => $orderNo
        );

        if (in_array($paymentKey, array(27, 41))) {
            $postData['invoice_ref'] = 'BNR-' . $postData['product']  . '-' . $orderNo;
            $orderComments = str_replace('NN_ORDER', $orderNo, $orderComments);
            self::performDbExecution('tbestellung', 'cKommentar = "' . $orderComments . '"' , 'cBestellNr ="' . $orderNo . '"');
        }
        // Core function to handle cURL call with the transaction request
        http_get_contents($this->novalnetPaygateUrl, $this->getGatewayTimeout(), $postData);
    }

    /**
     * To insert the order details into Novalnet tables
     *
     * @param array   $response
     * @param integer $orderValue
     * @param string  $paymentKey
     * @return none
     */
    public function insertOrderIntoDB($response, $orderValue, $paymentKey)
    {
        global $DB;

        $order = new Bestellung($orderValue); // Loads the order object from shop

        $tid = !empty($response['tid']) ? $response['tid'] : $_SESSION[$response['inputval3']]['tid'];

        $vendorId = $this->helper->getConfigurationParams('vendorid');
        $authcode = $this->helper->getConfigurationParams('authcode');

        if (!empty($_SESSION['nn_aff_id'])) {
            $affDetails = $this->helper->getAffiliateDetails(); // Get affiliate details for the current user
            $vendorId = $affDetails->vendorid;
            $authcode = $affDetails->cAffAuthcode;
        }

		$customerObj = new Kunde($order->kKunde);
        $insertOrder = new stdClass();
        $insertOrder->cNnorderid        = $order->cBestellNr;
        $insertOrder->cKonfigurations   = serialize(array('vendor' => $vendorId, 'auth_code' => $authcode, 'product' => $this->helper->getConfigurationParams('productid'), 'tariff' => $this->tariffValues[0], 'key' => $paymentKey));
        $insertOrder->nNntid            = $tid;
        $insertOrder->cZahlungsmethode  = !empty($response['inputval3']) ? $response['inputval3'] : $response['payment'];
        $insertOrder->cMail             = $customerObj->cMail;
        $insertOrder->nStatuswert       = !empty($response['tid_status']) ? $response['tid_status'] : $_SESSION[$response['inputval3']]['status'];
        $insertOrder->nBetrag           = !empty($response['key']) ? $this->helper->generateDecode($response['amount']) : ((!empty($response['amount']) ? $response['amount'] : $_SESSION['nn_amount']) * 100);
        $insertOrder->cSepaHash         = (!empty($_SESSION['novalnet_sepa']['nn_payment_hash']) && $_SESSION['Kunde']->nRegistriert != '0') ? $_SESSION['novalnet_sepa']['nn_payment_hash'] : '';
        $insertOrder->bOnetimeshopping  = !empty($_SESSION[$response['inputval3']]['one_click_shopping']) ? $_SESSION[$response['inputval3']]['one_click_shopping'] : 0;
        $insertOrder->cZeroBookingParams= !empty($_SESSION['nn_booking']) ? serialize($_SESSION['nn_booking']) : '';
        $insertOrder->cMaskedDetails    = in_array($paymentKey, array(6, 34, 37)) ? $this->getMaskedPatternToStore($response, $paymentKey, $insertOrder->bOnetimeshopping) : '';

        $DB->insertRow('xplugin_novalnetag_tnovalnet_status', $insertOrder);

        if (!in_array($paymentKey, array(27, 41, 78)) && $response['status'] == 100) { // If the payment server response is successful
            $insertCallback = new stdClass();
            $insertCallback->cBestellnummer  = $insertOrder->cNnorderid;
            $insertCallback->dDatum          = date('Y-m-d H:i:s');
            $insertCallback->cZahlungsart    = !empty($_SESSION['nn_request']['payment_type']) ? $_SESSION['nn_request']['payment_type'] : $response['payment_type'];
            $insertCallback->nReferenzTid    = $tid;
            $insertCallback->nCallbackAmount = $insertOrder->nBetrag;
            $insertCallback->cWaehrung       = isset($response['currency']) ? $response['currency'] : $_SESSION[$response['inputval3']]['currency'];

            $DB->insertRow('xplugin_novalnetag_tcallback', $insertCallback);
        }

        if (!empty($response['subs_id'])) { // If the order is a subscription order

            $insertSubscription = new stdClass();
            $insertSubscription->cBestellnummer = $insertOrder->cNnorderid;
            $insertSubscription->nSubsId        = $response['subs_id'];
            $insertSubscription->nTid           = $tid;
            $insertSubscription->dSignupDate    = date('Y-m-d H:i:s');

            $DB->insertRow('xplugin_novalnetag_tsubscription_details', $insertSubscription);
        }

        if (!empty($_SESSION['nn_aff_id'])) { // If the order is an affiliate order

            $insertAffiliate = new stdClass();
            $insertAffiliate->nAffId      = $vendorId;
            $insertAffiliate->cCustomerId = $order->kKunde;
            $insertAffiliate->nAffOrderNo = $insertOrder->cNnorderid;

            $DB->insertRow('xplugin_novalnetag_taff_user_detail', $insertAffiliate);
        }
    }

    /**
     * Assign and getback the masked pattern details
     *
     * @param array   $response
     * @param integer $paymentKey
     * @param integer $referenceOrder
     * @return array
     */
    function getMaskedPatternToStore($response, $paymentKey, $referenceOrder)
    {
        if ($this->helper->getConfigurationParams('extensive_option', $response['inputval3']) != '1' || $referenceOrder || empty($_SESSION['Kunde']->kKunde)) {
            return '';
        }

        switch($paymentKey)
        {
            case 6:
                return serialize(array(
                    'referenceOption1' => $response['cc_card_type'],
                    'referenceOption2' => utf8_decode($response['cc_holder']),
                    'referenceOption3' => $response['cc_no'],
                    'referenceOption4' => $response['cc_exp_month'] .'/'. $response['cc_exp_year']
                ) );

            case 34:
                return serialize(array(
                    'referenceOption1' => $response['paypal_transaction_id'] != '' ? $response['paypal_transaction_id'] : '',
                    'referenceOption2' => $response['tid']
                ) );

            case 37:
                return serialize(array(
                    'referenceOption1' => utf8_decode($response['bankaccount_holder']),
                    'referenceOption2' => $response['iban'],
                    'referenceOption3' => $response['bic'] != '123456' ? $response['bic'] : ''
                ) );
        }
    }

    /**
     * To insert the order details into Novalnet table for failure
     *
     * @param array        $response
     * @param object       $order
     * @param integer|null $paymentKey
     * @return bool
     */
    public function insertOrderIntoDBForFailure($response, $order, $paymentKey = '')
    {
        global $DB;

        $vendorId = $this->helper->getConfigurationParams('vendorid');
        $authcode = $this->helper->getConfigurationParams('authcode');

        $affiliateDetails = $this->helper->getAffiliateDetails(); // Get affiliate details for the current user

        if (!empty($affiliateDetails)) {
            $vendorId = $affiliateDetails->vendorid;
            $authcode = $affiliateDetails->cAffAuthcode;
        }

        $insertOrder = new stdClass();
        $insertOrder->cNnorderid        = $order->cBestellNr;
        $insertOrder->cKonfigurations   = serialize(array('vendor' => $vendorId, 'auth_code' => $authcode, 'product' => $this->helper->getConfigurationParams('productid'), 'tariff' => $this->tariffValues[0], 'key' => $paymentKey));
        $insertOrder->nNntid            = $response['tid'];
        $insertOrder->cZahlungsmethode  = !empty($response['inputval3']) ? $response['inputval3'] : $response['payment'];
        $insertOrder->cMail             = $response['email'];
        $insertOrder->nStatuswert       = $response['status'];
        $insertOrder->nBetrag           = gibPreisString($order->fGesamtsumme) * 100;
        $insertOrder->cSepaHash		 	= '';

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
        global $hinweis;

        $error = isset($_SESSION['nn_error']) ? $_SESSION['nn_error'] : (isset($_SESSION['fraud_check_error']) ? $_SESSION['fraud_check_error'] : '');

        if (!empty($error)) {
            $hinweis = $error;
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
        return (utf8_decode(!empty($response['status_desc']) ? $response['status_desc'] : (!empty($response['status_text']) ? $response['status_text'] : (!empty($response['status_message']) ? $response['status_message'] : $this->oPlugin->oPluginSprachvariableAssoc_arr['__NN_transaction_error']))));
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
    /**
     * Check to confirm guarantee payment option execution
     *
     * @param string $payment
     * @return bool
     */
    public function checkGuaranteedPaymentOption($payment)
    {
        if ($this->helper->getConfigurationParams('guarantee', $payment) !== '0') {

            $shippingAddress = array($_SESSION['Lieferadresse']->cStrasse, $_SESSION['Lieferadresse']->cHausnummer, $_SESSION['Lieferadresse']->cPLZ, $_SESSION['Lieferadresse']->cOrt);

            $customerAddress = array($_SESSION['Kunde']->cStrasse, $_SESSION['Kunde']->cHausnummer, $_SESSION['Kunde']->cPLZ,$_SESSION['Kunde']->cOrt);

            $orderAmount = $_SESSION['Warenkorb']->gibGesamtsummeWaren( array(C_WARENKORBPOS_TYP_ARTIKEL), true) * 100;

            if (in_array($_SESSION['Kunde']->cLand, array('DE','AT','CH')) && $_SESSION['Waehrung']->cISO == 'EUR' && (($this->helper->getConfigurationParams('guarantee_min_amount', $payment) != '' ? $orderAmount >= $this->helper->getConfigurationParams('guarantee_min_amount', $payment) : $orderAmount >= 2000) && ($this->helper->getConfigurationParams('guarantee_max_amount', $payment) != '' ? $orderAmount <= $this->helper->getConfigurationParams('guarantee_max_amount', $payment) : $orderAmount <= 500000)) && $customerAddress == $shippingAddress) { // Condition to check whether payment guarantee option can be processed

                $_SESSION['nn_' . $payment . '_guarantee'] = TRUE;

                if (isset($_SESSION['nn_' . $payment . '_guarantee_error'])) {
                    unset($_SESSION['nn_' . $payment . '_guarantee_error']);
                }

            } elseif ($this->helper->getConfigurationParams('guarantee_force', $payment) !== '0') {

                if (isset($_SESSION['nn_' . $payment . '_guarantee'])) {
                    unset($_SESSION['nn_' . $payment . '_guarantee']);
                }
            } else {
                $_SESSION['nn_' . $payment . '_guarantee']       = TRUE;
                $_SESSION['nn_' . $payment . '_guarantee_error'] = TRUE;
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
        return ($this->helper->getConfigurationParams('novalnet_public_key') == '' || empty($this->tariffValues[0]));
    }

    /**
     * Validates mandatory configuration parameters and customer parameters before requesting to the payment server
     *
     * @param array  $paymentRequestParameters
     * @param object $order
     * @return bool
     */
    public function preValidationCheckOnSubmission($paymentRequestParameters, $order)
    {
        if ($_SESSION['Zahlungsart']->nWaehrendBestellung == 0) {
            $_SESSION['nn_during_order'] = TRUE;
        }

        if ((empty($paymentRequestParameters['first_name']) && empty($paymentRequestParameters['last_name'])) || !valid_email($paymentRequestParameters['email'])) { // Validates the server customer mandatory parameters and sets the error if not configured properly

            $_SESSION['nn_error'] = $this->oPlugin->oPluginSprachvariableAssoc_arr['__NN_customer_details_error'];
        }

        if (!empty($_SESSION['nn_error'])) {
            // Redirects to the error page
            $this->redirectOnError($order, $paymentRequestParameters);
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

        if (!empty($_SESSION['nn_' . $paymentType . '_guarantee']) && !empty($_SESSION['nn_' . $paymentType . '_guarantee_error'])) {
            $_SESSION['nn_error'] = $this->oPlugin->oPluginSprachvariableAssoc_arr['__NN_guarantee_basic_error'];

        } elseif (!empty($_POST['nn_dob'])) { // Condition to check if the birthdate is eligible for guarantee payment

            if (time() < strtotime('+18 years', strtotime($_POST['nn_dob']))) {
                if (isset($_SESSION['nn_' . $paymentType . '_guarantee'])) {
					unset($_SESSION['nn_' . $paymentType . '_guarantee']);
				}
                if ($this->helper->getConfigurationParams('guarantee_force', $paymentType) == '0') {
                    $_SESSION['nn_error'] = $this->oPlugin->oPluginSprachvariableAssoc_arr['__NN_age_limit_error'];
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

        return !(($pinAmount && $pinAmount > 0 && (($_SESSION['Warenkorb']->gibGesamtsummeWaren( array(C_WARENKORBPOS_TYP_ARTIKEL), true) * 100) < $pinAmount)) || (!in_array($_SESSION['Kunde']->cLand, array('DE','AT','CH'))) || $_SESSION['Zahlungsart']->nWaehrendBestellung == 0 || !empty($_SESSION['nn_' . $payment . '_guarantee']));
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

        if ($currentOrderAmount != $_SESSION['nn_amount']) { // Condition to verify the order amount with the amount sent during first payment call
            $_SESSION['fraud_check_error'] = $this->oPlugin->oPluginSprachvariableAssoc_arr['__NN_amount_fraudmodule_error'];

            $this->helper->novalnetSessionCleanUp($payment); // Unset the entire novalnet session on order completion

            header('Location:' . $shopUrl . '/bestellvorgang.php?editZahlungsart=1');
            exit;
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
        if (!isset(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
}
