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
 * Script: NovalnetPrepayment.class.php
 *
 */
require_once(PFAD_ROOT . PFAD_INCLUDES_MODULES . 'PaymentMethod.class.php');
require_once('NovalnetGateway.class.php');

/**
 * Class NovalnetPrepayment
 */
class NovalnetPrepayment extends PaymentMethod
{
    /**
     * @var string
     */
    public $moduleID;

    /**
     * @var string
     */
    public $paymentName = 'novalnet_prepayment';

    /**
     * @var string
     */
    public $paymentKey = 27;

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
        $this->moduleID = 'kPlugin_' . $this->novalnetGateway->helper->oPlugin->kPlugin . '_novalnetvorauskasse';

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

        $this->name    = 'Novalnet Vorauskasse';
        $this->caption = 'Novalnet Vorauskasse';

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
        return !(!$this->novalnetGateway->helper->getConfigurationParams('enablemode', $this->paymentName) || $this->novalnetGateway->isConfigInvalid());
    }

    /**
     * Core function - Called when additional template is used
     *
     * @param array $aPost_arr
     * @return bool
     */
    public function handleAdditional($aPost_arr)
    {
        global $smarty, $shopVersion;

        $smarty->assign( array(
            'shopLatest'      => $shopVersion == '4x',
            'paymentTestmode' => $this->novalnetGateway->helper->getConfigurationParams('testmode', $this->paymentName),
            'nnLanguage'      => nnGetLanguageText(array('__NN_testmode', '__NN_invoice_description')) // Gets language texts for the variables
        ) );

        if (isset($aPost_arr['nn_payment'])) { // Only pass the payment step if the payment has been set
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

        $paymentRequestParameters = $this->novalnetGateway->generatePaymentParams($order, $this->paymentName); // Retrieves payment parameters for the transaction

        $paymentRequestParameters['key']          = $this->paymentKey;
        $paymentRequestParameters['invoice_type'] = 'PREPAYMENT';
        $paymentRequestParameters['payment_type'] = 'INVOICE_START';

        if ($this->duringCheckout == 0) {
            $paymentRequestParameters['invoice_ref'] = 'BNR-' . $paymentRequestParameters['product']  . '-' . $paymentRequestParameters['order_no'];
        }

        $this->novalnetGateway->preValidationCheckOnSubmission($paymentRequestParameters, $order, $this->paymentName); // Validates whether the transaction can be passed to the server

        $_SESSION['nn_request'] = $paymentRequestParameters;

        if ($this->duringCheckout == 0) {
            // Do server call when payment before order completion option is set to 'Nein'
            $this->novalnetGateway->performServerCall($this->paymentName);
            // Finalises the order based on response
            $this->novalnetGateway->verifyNotification($order, $this->paymentName, $this->paymentKey);
            // If the payment is done through after order completion process
            header('Location:' . $this->getNotificationURL($orderHash));
            exit;
        } else {
            // If the payment is done through during ordering process
            header('Location:' . $this->getNotificationURL($orderHash) . '&sh=' . $orderHash);
            exit;
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
        $this->novalnetGateway->performServerCall($this->paymentName); // Do server call when payment before order completion option is set to 'Ja'
        return $this->novalnetGateway->verifyNotification($order, $this->paymentName, $this->paymentKey); // Finalises the order based on response
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

        $this->insertOrderIntoInvPaymentsTable($order->cBestellNr); // Inserts values into xplugin_novalnetag_tpreinvoice_transaction_details table for amount update option

        $this->novalnetGateway->handlePaymentCompletion($order, $this->generateHash($order), $this->paymentKey, $this->paymentName); // Completes the order
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

        NovalnetGateway::performDbExecution('tbestellung', 'cStatus=' . constant($this->novalnetGateway->helper->getConfigurationParams('set_order_status', $this->paymentName)), 'cBestellNr = "' . $order->cBestellNr . '"'); // Updates the value into the database
    }

    /**
     * Logs Invoice bank details into database for later use
     *
     * @param string|integer $orderno
     * @return none
     */
    public function insertOrderIntoInvPaymentsTable($orderno)
    {
        global $DB;

        $insertPrepaymentDetails                    = new stdClass();
        $insertPrepaymentDetails->cBestellnummer    = $orderno;
        $insertPrepaymentDetails->bTestmodus        = $_SESSION[$this->paymentName]['test_mode'];
        $insertPrepaymentDetails->cbankName         = $_SESSION[$this->paymentName]['invoice_bankname'];
        $insertPrepaymentDetails->cbankCity         = $_SESSION[$this->paymentName]['invoice_bankplace'];
        $insertPrepaymentDetails->cbankIban         = $_SESSION[$this->paymentName]['invoice_iban'];
        $insertPrepaymentDetails->cbankBic          = $_SESSION[$this->paymentName]['invoice_bic'];
        $insertPrepaymentDetails->cRechnungDuedate  = $_SESSION[$this->paymentName]['due_date'];
        $insertPrepaymentDetails->cReferenceValues  = serialize($this->novalnetGateway->helper->getInvoicePaymentsReferences( $this->paymentName));

        $DB->insertRow('xplugin_novalnetag_tpreinvoice_transaction_details', $insertPrepaymentDetails);
    }
}
