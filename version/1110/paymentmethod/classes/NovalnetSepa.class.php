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
 * Script: NovalnetSepa.class.php
 *
 */
require_once(PFAD_ROOT . PFAD_INCLUDES_MODULES . 'PaymentMethod.class.php');
require_once('NovalnetGateway.class.php');

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

        if (!empty($_SESSION['nn_error']) && empty($_SESSION[$this->paymentName]['one_click_shopping'])) {
            $_SESSION[$this->paymentName]['form_error'] = true;
        }
    }

    /**
     * Core function - Sets the name and caption for the payment method and necessary when synchronizing with WAWI
     *
     * @param  int    $nAgainCheckout
     * @return object
     */
    public function init($nAgainCheckout = 0)
    {
        parent::init($nAgainCheckout);

        $this->name    = 'Novalnet Lastschrift SEPA';
        $this->caption = 'Novalnet Lastschrift SEPA';

        return $this;
    }

    /**
     * Core function - Called on payment page
     *
     * @param array $args_arr
     * @return bool
     */
    public function isValidIntern($args_arr = array())
    {
        return !((isset($_SESSION[$this->paymentName . '_invalid']) && (time() < $_SESSION[$this->paymentName . '_time_limit'])) || !$this->helper->getConfigurationParams('enablemode', $this->paymentName) || $this->novalnetGateway->isConfigInvalid());
    }

    /**
     * Core function - Called when additional template is used
     *
     * @param object $aPost_arr
     * @return bool
     */
    public function handleAdditional($aPost_arr)
    {
        global $smarty, $shopUrl, $shopVersion;

        $referenceTid = $this->helper->getPaymentReferenceValues($this->paymentName, 'nNntid'); // Gets reference TID for reference transaction

        $this->novalnetGateway->checkGuaranteedPaymentOption($this->paymentName);

        if (empty($_SESSION['nn_' . $this->paymentName . '_guarantee'])) {
            $this->novalnetGateway->displayFraudCheck($this->paymentName); // To display additional form fields for fraud prevention setup
        }

        $placeholder = array( '__NN_sepa_holder_name', '__NN_sepa_country_name', '__NN_sepa_account_number', '__NN_sepa_bank_code','__NN_guarantee_birthdate', '__NN_sepa_mandate_error','__NN_sepa_error', '__NN_sepa_mandate_text', '__NN_sepa_description', '__NN_javascript_error', '__NN_callback_phone_number','__NN_callback_sms', '__NN_callback_pin','__NN_callback_forgot_pin', '__NN_callback_telephone_error','__NN_callback_mobile_error', '__NN_callback_pin_error','__NN_callback_pin_error_empty', '__NN_testmode','__NN_account_details_link_old', '__NN_account_details_link_new', '__NN_birthdate_error', '__NN_birthdate_valid_error');

        $affDetails = $this->helper->getAffiliateDetails(); // Get affiliate details for the current user

        $smarty->assign( array(
            'templateFile'         => PFAD_ROOT . PFAD_PLUGIN . $this->oPlugin->cVerzeichnis . '/' . PFAD_PLUGIN_VERSION . $this->oPlugin->nVersion . '/' . PFAD_PLUGIN_PAYMENTMETHOD . 'template/'
            . $shopVersion . '/sepa.tpl',
            'payment_name'         => $_SESSION['Zahlungsart']->angezeigterName[$_SESSION['cISOSprache']],
            'vendor_id'            => empty($affDetails->vendorid) ? $this->helper->getConfigurationParams('vendorid') : $affDetails->vendorid,
            'auth_code'            => empty($affDetails->cAffAuthcode) ? $this->helper->getConfigurationParams('authcode') : $affDetails->cAffAuthcode,
            'test_mode'            => $this->helper->getConfigurationParams('testmode', $this->paymentName),
            'guarantee_force'      => $this->helper->getConfigurationParams('guarantee_force', $this->paymentName),
            'uniq_value'           => nnGetRandomString(), // Generates unique ID for the payment
            'sepa_holder'          => $_SESSION['Kunde']->cVorname . ' ' . $_SESSION['Kunde']->cNachname,
            'is_payment_guarantee' => !empty($_SESSION['nn_' . $this->paymentName . '_guarantee']) ? $_SESSION['nn_' . $this->paymentName . '_guarantee'] : '',
            'paymentMethodPath'    => $shopUrl . '/' . PFAD_PLUGIN . $this->oPlugin->cVerzeichnis . '/' . PFAD_PLUGIN_VERSION . $this->oPlugin->nVersion . '/' . PFAD_PLUGIN_PAYMENTMETHOD,
            'country_list'         => gibBelieferbareLaender($_SESSION['Kunde']->kKundengruppe),
            'panhash'              => $this->getSepaRefillHash(),
            'form_error'           => !empty($_SESSION[$this->paymentName]['form_error']) ? $_SESSION[$this->paymentName]['form_error'] : '',
            'nn_lang'              => nnGetLanguageText($placeholder), // Get language texts for the variables
        ) );

        if ($this->helper->getConfigurationParams('extensive_option', $this->paymentName) == '1' && empty($_SESSION[$this->paymentName]['tid']) && !empty($referenceTid)) { // Condition to check reference transaction
            $smarty->assign('one_click_shopping', true);
            $smarty->assign('nn_saved_details', unserialize($this->helper->getPaymentReferenceValues($this->paymentName, 'cMaskedDetails')));
        }

        if (!$this->novalnetGateway->basicValidationOnhandleAdditional($this->paymentName)) { // Validation on displaying payment form before submission
            return false;
        } elseif (isset($aPost_arr['nn_payment'])) { // Only pass the payment step if the payment has been set
            $_SESSION[$this->paymentName] = array_merge(isset($_SESSION[$this->paymentName]) ? (array)$_SESSION[$this->paymentName] : array(), array_map('trim', $aPost_arr));
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
     * Core function - Called at the time when 'Buy now' button is clicked, initiates the Payment process
     *
     * @param object $order
     * @return none
     */
    public function preparePaymentProcess($order)
    {
        // Process when reordering the payment from My-account (for shop 3.x series)
        if (!empty($_REQUEST['kBestellung'])) {
            $this->novalnetGateway->handleReorderProcess($order);
        }

        $orderHash = $this->generateHash($order); // Core function - To generate order hash

        if (!empty($_SESSION['nn_' . $this->paymentName . '_tid'])) { // Performs second call for fraud module after payment call success
            $this->novalnetGateway->doSecondCall($order, $this->paymentName); // Fraud module xml call to complete the order
            header('Location:' . $this->getNotificationURL($orderHash) . '&sh=' . $orderHash);
            exit;
        } else {

            $paymentRequestParameters = $this->novalnetGateway->generatePaymentParams($order, $this->paymentName); // Retrieves payment parameters for the transaction

            $paymentRequestParameters['key'] = 37;
            $paymentRequestParameters['payment_type'] = 'DIRECT_DEBIT_SEPA';
            $paymentRequestParameters['input3'] = 'payment';
            $paymentRequestParameters['inputval3'] = $this->paymentName;

            $extensiveOption = $this->helper->getConfigurationParams('extensive_option', $this->paymentName);

            if (!empty($_SESSION['nn_' . $this->paymentName . '_guarantee'])) { // Check to find whether the payment should be processed as a guaranteed payment
                $paymentRequestParameters['key'] = 40;
                $paymentRequestParameters['payment_type'] = 'GUARANTEED_DIRECT_DEBIT_SEPA';
                $paymentRequestParameters['birth_date'] = date('Y-m-d', strtotime($_SESSION[$this->paymentName]['nn_dob']));
            } elseif ($extensiveOption == '2') {// Condition to check zero amount booking
                unset($paymentRequestParameters['on_hold']); // Restrict keeping the booking payment as on-hold
                $_SESSION['nn_booking'] = $paymentRequestParameters;
            }

            if (!empty($_SESSION[$this->paymentName]['one_click_shopping'])) { // Condition to reference transaction
                $paymentRequestParameters['payment_ref'] = $this->helper->getPaymentReferenceValues($this->paymentName, 'nNntid');
            } else {

                if ($extensiveOption == '1' || ($extensiveOption == '2' && empty($_SESSION['nn_' . $this->paymentName . '_guarantee']))) {
                    $paymentRequestParameters['create_payment_ref'] = 1;
                }

                $paymentRequestParameters['bank_account_holder']= $_SESSION[$this->paymentName]['nn_sepaowner'];
                $paymentRequestParameters['iban_bic_confirmed'] = 1;
                $paymentRequestParameters['sepa_unique_id']     = $_SESSION[$this->paymentName]['nn_sepaunique_id'];
                $paymentRequestParameters['sepa_hash']          = $_SESSION[$this->paymentName]['nn_payment_hash'];
            }

            $paymentRequestParameters['sepa_due_date'] = self::getSepaDuedate($this->helper->getConfigurationParams('sepa_due_date')); // Retrieves sepa due date from configuration

            $this->novalnetGateway->preValidationCheckOnSubmission($paymentRequestParameters, $order); // Validates whether the transaction can be passed to the server

            $_SESSION['nn_request'] = $paymentRequestParameters;

            if ($_SESSION['Zahlungsart']->nWaehrendBestellung == 0) {
                // Do server call when payment before order completion option is set to 'Nein'
                $this->novalnetGateway->performServerCall($this->paymentName);
                // Finalises the order based on response
                $this->novalnetGateway->verifyNotification($order, $this->paymentName, !empty($_SESSION['nn_' . $this->paymentName . '_guarantee']) ? 40 : 37);
                // If the payment is done through after order completion process
                header('Location:' . $this->getNotificationURL($orderHash));
                exit;
            } else {
                // If the payment is done through during ordering process
                header('Location:' . $this->getNotificationURL($orderHash) . '&sh=' . $orderHash);
                exit;
            }
        }
    }

    /**
     * Core function - Called on notification URL for while ordering process
     *
     * @param object $order
     * @param string $hash
     * @param array  $args
     * @return bool
     */
    public function finalizeOrder($order, $hash, $args)
    {
        if (empty($_SESSION['nn_' . $this->paymentName . '_tid'])) { // Condition to check whether the first payment call has been processed already
            $this->novalnetGateway->performServerCall($this->paymentName); // Do server call when payment before order completion option is set to 'Ja'
            if ($this->helper->getConfigurationParams('pin_by_callback', $this->paymentName) != '0' && $_SESSION[$this->paymentName]['status'] == 100) { // Setup fraud module values for second call and redirects to payment page to enter pin
                $this->novalnetGateway->setupFraudModuleValues($this->paymentName, $order->fGesamtsumme);
            }
        }
        return $this->novalnetGateway->verifyNotification($order, $this->paymentName, !empty($_SESSION['nn_' . $this->paymentName . '_guarantee']) ? 40 : 37);
    }

    /**
     * Core function - Called when order is finalized and created on notification URL
     *
     * @param object $order
     * @param string $paymentHash
     * @param array  $args
     * @return none
     */
    public function handleNotification($order, $paymentHash, $args)
    {
        $this->updateShopDatabase($order); // Adds the payment method into the shop and changes the order status

        $this->novalnetGateway->handlePaymentCompletion($order, $this->generateHash($order), !empty($_SESSION['nn_' . $this->paymentName . '_guarantee']) ? 40 : 37, $this->paymentName); // Completes the order
    }

    /**
     * Adds the payment method into the shop, updates notification ID, sets order status
     *
     * @param object $order
     * @return none
     */
    public function updateShopDatabase( $order )
    {
        if ($_SESSION[$this->paymentName]['tid_status'] == 100 && $this->helper->getConfigurationParams( 'extensive_option', $this->paymentName) != '2') { // Adds to incoming payments only if the status is 100 or completed
            $incomingPayment = new stdClass();
            $incomingPayment->fBetrag = $order->fGesamtsummeKundenwaehrung;
            $incomingPayment->cISO = $order->Waehrung->cISO;
            $incomingPayment->cHinweis = $_SESSION[$this->paymentName]['tid'];
            $this->name = $order->cZahlungsartName; // Retrieves and assigns payment name to the payment method object
            $this->addIncomingPayment($order, $incomingPayment); // Adds the current transaction into the shop's order table

            NovalnetGateway::performDbExecution('tbestellung', 'dBezahltDatum = now()', 'cBestellNr = "' .$order->cBestellNr . '"'); // Updates the value into the database
        }

        $this->updateNotificationID($order->kBestellung, $_SESSION[$this->paymentName]['tid']); // Updates transaction ID into shop for reference

        NovalnetGateway::performDbExecution('tbestellung', 'cStatus=' . constant($this->helper->getConfigurationParams('set_order_status', $this->paymentName)), 'cBestellNr = "' . $order->cBestellNr . '"'); // Updates the value into the database
    }

    /**
     * Get panhash from database for sepa payment to process last successful order refill
     *
     * @param none
     * @return string|null
     */
    public function getSepaRefillHash()
    {
        global $DB, $shopQuery;

        $storedHash = '';

        if ($this->helper->getConfigurationParams('sepa_refill')) { // Condition to check if the SEPA payment refill is enabled

            $storedHash = $DB->$shopQuery('SELECT cSepaHash FROM xplugin_novalnetag_tnovalnet_status WHERE cMail = "' . $_SESSION['Kunde']->cMail . '" AND cZahlungsmethode = "' . $this->paymentName . '" ORDER BY kSno DESC LIMIT 1', 1);
        }

        return (($this->helper->getConfigurationParams('sepa_autorefill') && !empty($_SESSION[$this->paymentName]['nn_payment_hash'])) ? $_SESSION[$this->paymentName]['nn_payment_hash'] : (!empty($storedHash) ? $storedHash->cSepaHash : ''));
    }

    /**
     * To get the Novalnet SEPA duedate in days
     *
     * @param integer $dueDate
     * @return date
     */
    public static function getSepaDuedate($dueDate)
    {
        return (nnIsDigits($dueDate) && $dueDate > 6) ? date('Y-m-d', strtotime('+' . $dueDate . 'days')) : date( 'Y-m-d', strtotime('+7 days'));
    }
}
