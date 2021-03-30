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
 * Script: NovalnetBanktransfer.class.php
 *
 */
require_once(PFAD_ROOT . PFAD_INCLUDES_MODULES . 'PaymentMethod.class.php');
require_once('NovalnetGateway.class.php');

/**
 * Class NovalnetBanktransfer
 */
class NovalnetBanktransfer extends PaymentMethod
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
     * @var string
     */
    public $novalnetBanktransferUrl = 'https://payport.novalnet.de/online_transfer_payport';

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->paymentName = 'novalnet_banktransfer';

        // Creates instance for the NovalnetGateway class
        $this->novalnetGateway = NovalnetGateway::getInstance();

        // Creates instance for the NovalnetHelper class
        $this->helper = new NovalnetHelper();

        // Retrieves Novalnet plugin object
        $this->oPlugin = nnGetPluginObject();

        // Sets and displays payment error
        $this->novalnetGateway->assignPaymentError();
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

        $this->name    = 'Novalnet Sofortüberweisung';
        $this->caption = 'Novalnet Sofortüberweisung';

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
        return !(!$this->helper->getConfigurationParams('enablemode', $this->paymentName) || $this->novalnetGateway->isConfigInvalid());
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

        $this->helper->novalnetSessionUnset($this->paymentName); // Unsets the other Novalnet payment sessions

        $smarty->assign( array(
            'payment_name' => $this->paymentName,
            'test_mode'    => $this->helper->getConfigurationParams('testmode', $this->paymentName),
            'shopLatest'   => $shopVersion == '4x',
            'nn_lang'      => nnGetLanguageText(array('__NN_testmode', '__NN_redirection_text', '__NN_redirection_browser_text')) // Gets language texts for the variables
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
        global $smarty, $shopUrl, $shopVersion;

        // Process when reordering the payment from My-account (for shop 3.x series)
        if (!empty($_REQUEST['kBestellung'])) {
            $this->novalnetGateway->handleReorderProcess($order);
        }

        $orderHash = $this->generateHash($order); // Core function - To generate order hash

        $paymentRequestParameters = $this->novalnetGateway->generatePaymentParams($order, $this->paymentName); // Retrieves payment parameters for the transaction

        $paymentRequestParameters['key'] = 33;
        $paymentRequestParameters['payment_type'] = 'ONLINE_TRANSFER';
        $paymentRequestParameters['input3'] = 'payment';
        $paymentRequestParameters['inputval3'] = $this->paymentName;

        $this->novalnetGateway->preValidationCheckOnSubmission($paymentRequestParameters, $order); // Validates whether the transaction can be passed to the server

        $handlerUrlParameters = $this->getPaymentReturnUrls($orderHash); // Retrieves return URL's for redirection payment

        $paymentRequestParameters['return_url']         = $handlerUrlParameters['cReturnURL'];
        $paymentRequestParameters['return_method']      = 'POST';
        $paymentRequestParameters['error_return_url']   = $handlerUrlParameters['cFailureURL'];
        $paymentRequestParameters['error_return_method']= 'POST';
        $paymentRequestParameters['session']            = session_id();
        $paymentRequestParameters['user_variable_0']    = $shopUrl;
        $paymentRequestParameters['uniqid']             = uniqid();
        $paymentRequestParameters['implementation']     = 'PHP';

        $this->helper->generateEncodeArray($paymentRequestParameters);
        $paymentRequestParameters['hash'] = $this->helper->generateHashValue($paymentRequestParameters, 33); // Encodes the basic payment parameters before sending to third party

        $smarty->assign( array(
            'paymentUrl'       => $this->novalnetBanktransferUrl,
            'datas'            => $paymentRequestParameters,
            'browser_message'  => $this->oPlugin->oPluginSprachvariableAssoc_arr['__NN_redirection_browser_text'],
            'button_text'      => $this->oPlugin->oPluginSprachvariableAssoc_arr['__NN_redirection_button_text'],
            'shopLatest'       => $shopVersion == '4x',
            'paymentMethodPath'=> $shopUrl . '/' . PFAD_PLUGIN . $this->oPlugin->cVerzeichnis . '/' . PFAD_PLUGIN_VERSION . $this->oPlugin->nVersion . '/' . PFAD_PLUGIN_PAYMENTMETHOD
        ) );
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
        return $this->novalnetGateway->verifyNotification($order, $this->paymentName, 33, $args); // Finalises the order based on response
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
        $this->updateShopDatabase($order, $args['tid']); // Adds the payment method into the shop and changes the order status

        $this->novalnetGateway->handlePaymentCompletion($order, $this->generateHash($order), 33, $this->paymentName, $args); // Completes the order
    }

    /**
     * Adds the payment method into the shop, updates notification ID, sets order status
     *
     * @param object $order
     * @param integer $notificationID
     * @return none
     */
    public function updateShopDatabase($order, $notificationID)
    {
        $incomingPayment = new stdClass();
        $incomingPayment->fBetrag = $order->fGesamtsummeKundenwaehrung;
        $incomingPayment->cISO = $order->Waehrung->cISO;
        $incomingPayment->cHinweis = $notificationID;
        $this->name = $order->cZahlungsartName; // Retrieves and assigns payment name to the payment method object
        $this->addIncomingPayment($order, $incomingPayment); // Adds the current transaction into the shop's order table

        $this->updateNotificationID($order->kBestellung, $notificationID); // Updates transaction ID into shop for reference

        NovalnetGateway::performDbExecution('tbestellung', 'dBezahltDatum = now(), cStatus=' . constant($this->helper->getConfigurationParams('set_order_status', $this->paymentName)), 'cBestellNr = "' . $order->cBestellNr . '"'); // Updates the value into the database
    }

    /**
     * Set return URLs for redirection payments
     *
     * @param string $orderHash
     * @return array $handlerUrlParameters
     */
    public function getPaymentReturnUrls($orderHash)
    {
        if ($_SESSION['Zahlungsart']->nWaehrendBestellung == 0) {
            $handlerUrlParameters['cReturnURL'] = $handlerUrlParameters['cFailureURL'] = $this->getNotificationURL($orderHash);
            return $handlerUrlParameters;
        }

        $handlerUrlParameters['cReturnURL'] = $handlerUrlParameters['cFailureURL'] = $this->getNotificationURL($orderHash) . '&sh=' . $orderHash;

        return $handlerUrlParameters;
    }
}
