<?php
/**
 * Novalnet payment method module
 * This module is used for real time processing of
 * Novalnet transaction of customers.
 *
 * Copyright (c) Novalnet
 *
 * Released under the GNU General Public License
 * This free contribution made by request.
 * If you have found this script useful a small
 * recommendation as well as a comment on merchant form
 * would be greatly appreciated.
 *
 * Script: NovalnetInvoice.class.php
 *
 */
require_once(PFAD_ROOT . PFAD_INCLUDES_MODULES . 'PaymentMethod.class.php');
require_once('NovalnetGateway.class.php');

/**
 * Class NovalnetInvoice
 */
class NovalnetInvoice extends PaymentMethod
{
    /**
     * @var string
     */
    public $moduleID;

    /**
     * @var string
     */
    public $paymentName = 'novalnet_invoice';

    /**
     * @var string
     */
    public $paymentKey = 27;

    /**
     * @var string
     */
    public $paymentGuaranteeKey = 41;

    /**
     * @var null|NovalnetGateway
     */
    public $novalnetGateway = null;

    /**
     * Constructor
     */
    public function __construct($moduleID)
    {
        // Creates instance for the NovalnetGateway class
        $this->novalnetGateway = NovalnetGateway::getInstance();

        // Sets and displays payment error
        $this->novalnetGateway->assignPaymentError();

        // Setting up module ID for the payment class
        $this->moduleID = 'kPlugin_' . $this->novalnetGateway->helper->oPlugin->kPlugin . '_novalnetkaufaufrechnung';

        parent::__construct($moduleID);
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

        $this->name    = 'Novalnet Kauf auf Rechnung';
        $this->caption = 'Novalnet Kauf auf Rechnung';

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
        return !((isset($_SESSION[$this->paymentName . '_invalid']) && (time() < $_SESSION[$this->paymentName . '_time_limit'])) || !$this->novalnetGateway->helper->getConfigurationParams('enablemode', $this->paymentName) || $this->novalnetGateway->isConfigInvalid());
    }

    /**
     * Core function - Called when additional template is used
     *
     * @param array $aPost_arr
     * @return bool
     */
    public function handleAdditional($aPost_arr)
    {
        global $smarty, $shopVersion, $shopUrl;

        $this->novalnetGateway->checkGuaranteedPaymentOption($this->paymentName);

        if (empty($_SESSION['nn_' . $this->paymentName . '_guarantee'])) {
            $this->novalnetGateway->displayFraudCheck($this->paymentName); // To display additional form fields for fraud prevention setup
        }

        $placeholder = array('__NN_callback_phone_number', '__NN_callback_sms', '__NN_callback_pin', '__NN_callback_forgot_pin', '__NN_callback_telephone_error', '__NN_callback_mobile_error', '__NN_callback_pin_error', '__NN_callback_pin_error_empty','__NN_testmode', '__NN_invoice_description', '__NN_guarantee_birthdate','__NN_birthdate_error', '__NN_birthdate_valid_error');

        $smarty->assign( array(
            'templateFile'         => PFAD_ROOT . PFAD_PLUGIN . $this->novalnetGateway->helper->oPlugin->cVerzeichnis . '/' . PFAD_PLUGIN_VERSION . $this->novalnetGateway->helper->oPlugin->nVersion . '/' . PFAD_PLUGIN_PAYMENTMETHOD . 'template/'
            . $shopVersion . '/invoice.tpl',
            'payment_name'         => $_SESSION['Zahlungsart']->angezeigterName[$_SESSION['cISOSprache']],
            'testMode'            => $this->novalnetGateway->helper->getConfigurationParams('testmode', $this->paymentName),
            'guaranteeForce'      => $this->novalnetGateway->helper->getConfigurationParams('guarantee_force', $this->paymentName),
            'isPaymentGuarantee' => !empty($_SESSION['nn_' . $this->paymentName . '_guarantee']) ? $_SESSION['nn_' . $this->paymentName . '_guarantee'] : '',
            'paymentMethodPath'    => $shopUrl . '/' . PFAD_PLUGIN . $this->novalnetGateway->helper->oPlugin->cVerzeichnis . '/' . PFAD_PLUGIN_VERSION . $this->novalnetGateway->helper->oPlugin->nVersion . '/' . PFAD_PLUGIN_PAYMENTMETHOD,
            'nnLang'              => nnGetLanguageText($placeholder) // Get language texts for the variables
        ) );

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

            $paymentRequestParameters['key']          = $this->paymentKey;
            $paymentRequestParameters['invoice_type'] = 'INVOICE';
            $paymentRequestParameters['payment_type'] = 'INVOICE_START';


            if (!empty($_SESSION['nn_' . $this->paymentName . '_guarantee'])) { // Check to find whether the payment should be processed as a guaranteed payment
                $paymentRequestParameters['key']          = $this->paymentGuaranteeKey;
                $paymentRequestParameters['payment_type'] = 'GUARANTEED_INVOICE';
                $paymentRequestParameters['birth_date']   = date('Y-m-d', strtotime($_SESSION[$this->paymentName]['nn_dob']));
            }

            $invoiceDuedate = $this->getInvoiceDuedate(); // Calculates Invoice payment duedate for the order

            if (!empty($invoiceDuedate)) {
                $paymentRequestParameters['due_date'] = $invoiceDuedate;
            }

            if ($this->duringCheckout == 0) {
                $paymentRequestParameters['invoice_ref'] = 'BNR-' . $paymentRequestParameters['product']  . '-' . $paymentRequestParameters['order_no'];
            }

            $this->novalnetGateway->preValidationCheckOnSubmission($paymentRequestParameters, $order, $this->paymentName); // Validates whether the transaction can be passed to the server

            $_SESSION['nn_request'] = $paymentRequestParameters;

            if ($this->duringCheckout == 0) {
                // Do server call when payment before order completion option is set to 'Nein'
                $this->novalnetGateway->performServerCall($this->paymentName);
                // Finalises the order based on response
                $this->novalnetGateway->verifyNotification($order, $this->paymentName, !empty($_SESSION['nn_' . $this->paymentName . '_guarantee']) ? $this->paymentGuaranteeKey : $this->paymentKey);
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
            if ($this->novalnetGateway->helper->getConfigurationParams('pin_by_callback', $this->paymentName) != '0' && $_SESSION[$this->paymentName]['status'] == 100) { // Setup fraud module values for second call and redirects to payment page to enter pin
                $this->novalnetGateway->setupFraudModuleValues($this->paymentName, $order->fGesamtsumme);
            }
        }
        // Finalises the order based on response
        return $this->novalnetGateway->verifyNotification($order, $this->paymentName, !empty($_SESSION['nn_' . $this->paymentName . '_guarantee']) ? $this->paymentGuaranteeKey : $this->paymentKey);
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

        $this->insertOrderIntoInvoiceTable($order->cBestellNr); // Inserts values into xplugin_novalnetag_tpreinvoice_transaction_details table for amount update option

        $this->novalnetGateway->handlePaymentCompletion($order, $this->generateHash($order), !empty($_SESSION['nn_' . $this->paymentName . '_guarantee']) ? $this->paymentGuaranteeKey : $this->paymentKey, $this->paymentName); // Completes the order
    }

    /**
     * Adds the payment method into the shop, updates notification ID, sets order status
     *
     * @param object $order
     * @return none
     */
    public function updateShopDatabase($order)
    {
        $this->updateNotificationID($order->kBestellung, $_SESSION[$this->paymentName]['tid']); // Updates transaction ID into shop for reference

        NovalnetGateway::performDbExecution('tbestellung','cStatus=' . constant(!empty($_SESSION['nn_' . $this->paymentName . '_guarantee']) ? $this->novalnetGateway->helper->getConfigurationParams('callback_status', $this->paymentName) : $this->novalnetGateway->helper->getConfigurationParams('set_order_status', $this->paymentName)), 'cBestellNr = "' . $order->cBestellNr . '"'); // Updates the value into the database
    }

    /**
     * To get the Novalnet Invoice duedate in days
     *
     * @param none
     * @return integer|null
     */
    private function getInvoiceDuedate()
    {
        return (nnIsDigits($this->novalnetGateway->helper->getConfigurationParams('invoice_duration')) ? date('Y-m-d', strtotime('+' . $this->novalnetGateway->helper->getConfigurationParams('invoice_duration') . ' days')) : '');
    }

    /**
     * Logs Invoice bank details into database for later use
     *
     * @param string|integer $orderNo
     * @return none
     */
    public function insertOrderIntoInvoiceTable($orderNo)
    {
        global $DB;

        $insertInvoiceDetails                   = new stdClass();
        $insertInvoiceDetails->cBestellnummer   = $orderNo;
        $insertInvoiceDetails->bTestmodus       = $_SESSION[$this->paymentName]['test_mode'];
        $insertInvoiceDetails->cbankName        = $_SESSION[$this->paymentName]['invoice_bankname'];
        $insertInvoiceDetails->cbankCity        = $_SESSION[$this->paymentName]['invoice_bankplace'];
        $insertInvoiceDetails->cbankIban        = $_SESSION[$this->paymentName]['invoice_iban'];
        $insertInvoiceDetails->cbankBic         = $_SESSION[$this->paymentName]['invoice_bic'];
        $insertInvoiceDetails->cRechnungDuedate = $_SESSION[$this->paymentName]['due_date'];
        $insertInvoiceDetails->cReferenceValues = serialize($this->novalnetGateway->helper->getInvoicePaymentsReferences($this->paymentName));

        $DB->insertRow('xplugin_novalnetag_tpreinvoice_transaction_details', $insertInvoiceDetails);
    }
}
