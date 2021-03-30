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
 * Script: NovalnetGiropay.class.php
 *
 */
require_once(PFAD_ROOT . PFAD_INCLUDES_MODULES . 'PaymentMethod.class.php');
require_once('NovalnetGateway.class.php');

/**
 * Class NovalnetGiropay
 */
class NovalnetGiropay extends PaymentMethod
{
    /**
     * @var string
     */
    public $moduleID;

    /**
     * @var string
     */
    public $paymentName = 'novalnet_giropay';

    /**
     * @var string
     */
    public $paymentKey = 69;

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
    public $novalnetGiropayUrl = 'https://payport.novalnet.de/giropay';

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
        $this->moduleID = 'kPlugin_' . $this->novalnetGateway->helper->oPlugin->kPlugin . '_novalnetgiropay';

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

        $this->name    = 'Novalnet Giropay';
        $this->caption = 'Novalnet Giropay';

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
        $this->novalnetGateway->setupShopInformationText($this->moduleID, 'giropay');

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

        $this->novalnetGateway->helper->novalnetSessionUnset($this->paymentName); // Unsets the other Novalnet payment sessions

        $smarty->assign( array(
            'paymentName' => $this->paymentName,
            'testMode'    => $this->novalnetGateway->helper->getConfigurationParams('testmode', $this->paymentName),
            'shopLatest'  => $shopVersion == '4x',
            'nnLang'      => nnGetLanguageText(array('__NN_testmode', '__NN_redirection_text', '__NN_redirection_browser_text')) // Gets language texts for the variables
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

        $paymentRequestParameters['key']          = $this->paymentKey;
        $paymentRequestParameters['payment_type'] = 'GIROPAY';

        $this->novalnetGateway->preValidationCheckOnSubmission($paymentRequestParameters, $order, $this->paymentName); // Validates whether the transaction can be passed to the server

        $handlerUrlParameters = $this->getPaymentReturnUrls($orderHash, $order->kBestellung); // Retrieves return URL's for redirection payment

        $paymentRequestParameters['return_url']          = $handlerUrlParameters['cReturnURL'];
        $paymentRequestParameters['return_method']       = 'POST';
        $paymentRequestParameters['error_return_url']    = $handlerUrlParameters['cFailureURL'];
        $paymentRequestParameters['error_return_method'] = 'POST';
        $paymentRequestParameters['session']             = session_id();
        $paymentRequestParameters['user_variable_0']     = $shopUrl;
        $paymentRequestParameters['uniqid']              = uniqid();
        $paymentRequestParameters['implementation']      = 'PHP';

        $this->novalnetGateway->helper->generateEncodeArray($paymentRequestParameters);
        $paymentRequestParameters['hash'] = $this->novalnetGateway->helper->generateHashValue($paymentRequestParameters, 50); // Encodes the basic payment parameters before sending to third party

        $smarty->assign( array(
            'paymentUrl'        => $this->novalnetGiropayUrl,
            'datas'             => $paymentRequestParameters,
            'message'           => $this->novalnetGateway->helper->oPlugin->oPluginSprachvariableAssoc_arr['__NN_redirection_text'],
            'browserMessage'    => $this->novalnetGateway->helper->oPlugin->oPluginSprachvariableAssoc_arr['__NN_redirection_browser_text'],
            'buttonText'        => $this->novalnetGateway->helper->oPlugin->oPluginSprachvariableAssoc_arr['__NN_redirection_button_text'],
            'shopLatest'        => $shopVersion == '4x',
            'paymentMethodPath' => $shopUrl . '/' . PFAD_PLUGIN . $this->novalnetGateway->helper->oPlugin->cVerzeichnis . '/' . PFAD_PLUGIN_VERSION . $this->novalnetGateway->helper->oPlugin->nVersion . '/' . PFAD_PLUGIN_PAYMENTMETHOD
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
        return $this->novalnetGateway->verifyNotification($order, $this->paymentName, $this->paymentKey, $args); // Finalises the order based on response
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
        global $DB, $selectQuery;

        // Verify if the payment has been received already for the transaction
        $incomingPayment = $DB->$selectQuery(
                                'tzahlungseingang',
                                'kBestellung', $order->kBestellung,
                                'cHinweis', $args['tid']
                           );

        if (is_object($incomingPayment) && intval($incomingPayment->kZahlungseingang) > 0) {

            $this->novalnetGateway->completeProcess($order, $this->generateHash($order), $this->paymentName, $args);
            // Verifies if the payment has not been processed already
        } else {
            $this->updateShopDatabase($order, $args['tid']); // Adds the payment method into the shop and changes the order status

            $this->novalnetGateway->handlePaymentCompletion($order, $this->generateHash($order), $this->paymentKey, $this->paymentName, $args); // Completes the order
        }
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
        $incomingPayment           = new stdClass();
        $incomingPayment->fBetrag  = $order->fGesamtsummeKundenwaehrung;
        $incomingPayment->cISO     = $order->Waehrung->cISO;
        $incomingPayment->cHinweis = $notificationID;
        $this->name                = $order->cZahlungsartName; // Retrieves and assigns payment name to the payment method object
        $this->addIncomingPayment($order, $incomingPayment); // Adds the current transaction into the shop's order table

        $this->updateNotificationID($order->kBestellung, $notificationID); // Updates transaction ID into shop for reference

        NovalnetGateway::performDbExecution('tbestellung', 'dBezahltDatum = now(), cStatus=' . constant($this->novalnetGateway->helper->getConfigurationParams('set_order_status', $this->paymentName)), 'cBestellNr = "' . $order->cBestellNr . '"'); // Updates the value into the database
    }

    /**
     * Set return URLs for redirection payments
     *
     * @param string $orderHash
     * @param string $orderNo
     * @return array $handlerUrlParameters
     */
    public function getPaymentReturnUrls($orderHash, $orderNo)
    {
        if ($_SESSION['Zahlungsart']->nWaehrendBestellung == 0) {
            $handlerUrlParameters['cReturnURL'] = $handlerUrlParameters['cFailureURL'] = $this->getNotificationURL($orderHash);

            NovalnetGateway::performDbExecution('tbestellung', 'cAbgeholt="Y"', 'kBestellung ="' . $orderNo . '"');
            return $handlerUrlParameters;
        }

        $handlerUrlParameters['cReturnURL'] = $handlerUrlParameters['cFailureURL'] = $this->getNotificationURL($orderHash) . '&sh=' . $orderHash;

        return $handlerUrlParameters;
    }
}
