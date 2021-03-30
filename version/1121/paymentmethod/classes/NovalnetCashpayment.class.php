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
 * Script: NovalnetCashpayment.class.php
 *
 */
require_once(PFAD_ROOT . PFAD_INCLUDES_MODULES . 'PaymentMethod.class.php');
require_once('NovalnetGateway.class.php');

/**
 * Class NovalnetCashpayment
 */
class NovalnetCashpayment extends PaymentMethod
{
    /**
     * @var string
     */
    public $moduleID;

    /**
     * @var string
     */
    public $paymentName = 'novalnet_cashpayment';

    /**
     * @var string
     */
    public $paymentKey = 59;

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
    public function __construct($moduleID)
    {
        // Creates instance for the NovalnetGateway class
        $this->novalnetGateway = NovalnetGateway::getInstance();

        // Sets and displays payment error
        $this->novalnetGateway->assignPaymentError();

        // Setting up module ID for the payment class
        $this->moduleID = 'kPlugin_' . $this->novalnetGateway->helper->oPlugin->kPlugin . '_novalnetbarzahlen';

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

        $this->name    = 'Novalnet Barzahlen';
        $this->caption = 'Novalnet Barzahlen';

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
        // Update necessary for the shop version greater than 406 where shop information text is empty by default
        $this->novalnetGateway->setupShopInformationText($this->moduleID, 'cashpayment');

        return !(!$this->novalnetGateway->helper->getConfigurationParams('enablemode', $this->paymentName) ||
                $this->novalnetGateway->isConfigInvalid());
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

        $smarty->assign(array(
            'shopLatest'      => $shopVersion == '4x',
            'paymentTestmode' => $this->novalnetGateway->helper->getConfigurationParams('testmode', $this->paymentName),
            'nnLanguage'      => nnGetLanguageText(array('__NN_testmode', '__NN_cashpayment_description'))              // Gets language texts for the variables
        ));

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
        $paymentRequestParameters['payment_type'] = 'CASHPAYMENT';

        $expiryDate = $this->getCashpaymentSlipdate($this->novalnetGateway->helper->getConfigurationParams('cashpayment_slip_expiry')); // Calculates slip date to purchase the product

        if (!empty($expiryDate)) {
            $paymentRequestParameters['cp_due_date'] = $expiryDate;
        }

        $this->novalnetGateway->preValidationCheckOnSubmission($paymentRequestParameters, $order, $this->paymentName); // Validates whether the transaction can be passed to the server

        $_SESSION['nn_request'] = $paymentRequestParameters;

        if ($_SESSION['Zahlungsart']->nWaehrendBestellung == 0) {
            // Do server call when payment before order completion option is set to 'Nein'
            $this->novalnetGateway->performServerCall($this->paymentName);
            // Finalises the order based on response
            $this->novalnetGateway->verifyNotification($order, $this->paymentName, 59);
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
        // Do server call when payment before order completion option is set to 'Ja'
        $this->novalnetGateway->performServerCall($this->paymentName);
        // Finalises the order based on response
        return $this->novalnetGateway->verifyNotification($order, $this->paymentName, $this->paymentKey);
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

        if ($_SESSION[$this->paymentName]['status'] == 100 && $_SESSION[$this->paymentName]['cp_checkout_token']) {
            $_SESSION['novalnet_cashpayment_token'] = $_SESSION[$this->paymentName]['cp_checkout_token'];
            $_SESSION['novalnet_cashpayment_mode']  = (int) $_SESSION[$this->paymentName]['test_mode'];
        }

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
     * To get the Novalnet Cashpayment slip duedate in days
     *
     * @param integer $slipExpiryDate
     * @return integer|null
     */
    private function getCashpaymentSlipdate($slipExpiryDate)
    {
         return (nnIsDigits($slipExpiryDate)) ? date('Y-m-d', strtotime('+' . $slipExpiryDate . 'days')) : '';        
    }
}
