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
 * Script: NovalnetPaypal.class.php
 *
 */
require_once(PFAD_ROOT . PFAD_INCLUDES_MODULES . 'PaymentMethod.class.php');
require_once('NovalnetGateway.class.php');

/**
 * Class NovalnetPaypal
 */
class NovalnetPaypal extends PaymentMethod
{
    /**
     * @var string
     */
    public $moduleID;

    /**
     * @var string
     */
    public $paymentName = 'novalnet_paypal';

    /**
     * @var string
     */
    public $paymentKey = 34;

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
    public $novalnetPaypalUrl = 'https://payport.novalnet.de/paypal_payport';

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
        $this->moduleID = 'kPlugin_' . $this->novalnetGateway->helper->oPlugin->kPlugin . '_novalnetpaypal';

        if (!empty($_SESSION['nn_error']) && empty($_SESSION[$this->paymentName]['one_click_shopping'])) {
            $_SESSION[$this->paymentName]['form_error'] = true;
        }

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

        $this->name    = 'Novalnet PayPal';
        $this->caption = 'Novalnet PayPal';

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
        $this->novalnetGateway->setupShopInformationText($this->moduleID, 'paypal');

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
        global $smarty, $shopUrl, $shopVersion;
        // Unsets the other Novalnet payment sessions
        $this->novalnetGateway->helper->novalnetSessionUnset($this->paymentName);

        $referenceTid = $this->novalnetGateway->helper->getPaymentReferenceValues($this->paymentName, 'nNntid');  // Gets reference TID for reference transaction

        // if ($this->novalnetGateway->helper->getConfigurationParams('extensive_option', $this->paymentName) != '1') {
        //     return true;
        // }

        $placeholder = array(
                            '__NN_testmode',
                            '__NN_zero_booking_note',
                            '__NN_redirection_text',
                            '__NN_redirection_browser_text',
                            '__NN_paypal_account_details_link_old',
                            '__NN_paypal_account_details_link_new',
                            '__NN_paypal_desc',
                            '__NN_tid_label',
                            '__NN_oneclick_paypal_save_data',
                            '__NN_paypal_tid_label'
                        );

        $smarty->assign(array(
            'templateFile'      => PFAD_ROOT . PFAD_PLUGIN . $this->novalnetGateway->helper->oPlugin->cVerzeichnis . '/' . PFAD_PLUGIN_VERSION . $this->novalnetGateway->helper->oPlugin->nVersion . '/' . PFAD_PLUGIN_PAYMENTMETHOD . 'template/'
            . $shopVersion . '/paypal.tpl',
            'paymentName'       => $this->paymentName,
            'paymentMethodPath' => $shopUrl . '/' . PFAD_PLUGIN . $this->novalnetGateway->helper->oPlugin->cVerzeichnis . '/' . PFAD_PLUGIN_VERSION . $this->novalnetGateway->helper->oPlugin->nVersion . '/' . PFAD_PLUGIN_PAYMENTMETHOD,
            'testMode'          => $this->novalnetGateway->helper->getConfigurationParams('testmode', $this->paymentName),
            'zeroBooking'       => ($this->novalnetGateway->helper->getConfigurationParams('extensive_option', $this->paymentName) == '2'),
            'shopLatest'        => $shopVersion == '4x',
            'nnLang'            => nnGetLanguageText($placeholder) // Gets language texts for the variables
        ));

        if ($this->novalnetGateway->helper->getConfigurationParams('extensive_option', $this->paymentName) == '1') { // Condition to check reference transaction
            $smarty->assign('one_click_shopping', true);
         }
         if(!empty($referenceTid)) {   
            $smarty->assign(
                'nn_saved_details',
                unserialize(
                    $this->novalnetGateway->helper->getPaymentReferenceValues($this->paymentName, 'cAdditionalInfo')
                )
            );
        }

        if (isset($aPost_arr['nn_payment'])) { // Only pass the payment step if the payment has been set
            $_SESSION[$this->paymentName] = array_merge(isset($_SESSION[$this->paymentName])
                                                ? (array)$_SESSION[$this->paymentName]
                                                : array(), array_map('trim', $aPost_arr));
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
     * @return none|bool
     */
    public function preparePaymentProcess($order)
    {
        global $smarty, $shopUrl, $shopVersion;

        // Process when reordering the payment from My-account (for shop 3.x series)
        if (!empty($_REQUEST['kBestellung'])) {
            $this->novalnetGateway->handleReorderProcess($order);
        }

        $orderHash = $this->generateHash($order); // Core function - To generate order hash
        $uniqid = $this->novalnetGateway->helper->get_uniqueid();
        $paymentRequestParameters = $this->novalnetGateway->generatePaymentParams($order, $this->paymentName); // Retrieves payment parameters for the transaction

        $paymentRequestParameters['key']          = $this->paymentKey;
        $paymentRequestParameters['payment_type'] = 'PAYPAL';

        if ($this->novalnetGateway->helper->getConfigurationParams('extensive_option', $this->paymentName) == '2') { // Condition to check zero amount booking
            unset($paymentRequestParameters['on_hold']); // Restrict keeping the booking payment as on-hold
            $_SESSION['nn_booking'] = $paymentRequestParameters;
        }
        $extensiveOption = $this->novalnetGateway->helper->getConfigurationParams('extensive_option', $this->paymentName);
        $this->novalnetGateway->preValidationCheckOnSubmission($paymentRequestParameters, $order, $this->paymentName);  // Validates whether the transaction can be passed to the server

        if (empty($_SESSION[$this->paymentName]['one_click_shopping'])) {
            $handlerUrlParameters = $this->getPaymentReturnUrls($orderHash, $order->kBestellung);  // Retrieves return URL's for redirection payment

            $paymentRequestParameters['return_url']          = $handlerUrlParameters['cReturnURL'];
            $paymentRequestParameters['return_method']       = 'POST';
            $paymentRequestParameters['error_return_url']    = $handlerUrlParameters['cFailureURL'];
            $paymentRequestParameters['error_return_method'] = 'POST';
            $paymentRequestParameters['session']             = session_id();
            $paymentRequestParameters['user_variable_0']     = $shopUrl;
            $paymentRequestParameters['uniqid']              = $uniqid;
            $paymentRequestParameters['implementation']      = 'ENC';

            if (($extensiveOption == '1'  && ($_SESSION[$this->paymentName]['nn_save_payment']))|| ($extensiveOption == '2')) {
                $paymentRequestParameters['create_payment_ref'] = 1;
            }

            $this->novalnetGateway->helper->generateEncodeArray($paymentRequestParameters, $uniqid);
            $paymentRequestParameters['hash'] = $this->novalnetGateway->helper->generateHashValue($paymentRequestParameters, $this->paymentKey); // Encodes the basic payment parameters before sending to third party

            $smarty->assign(array(
                'paymentUrl'        => $this->novalnetPaypalUrl,
                'datas'             => $paymentRequestParameters,
                'message'           => $this->novalnetGateway->helper->oPlugin->oPluginSprachvariableAssoc_arr['__NN_redirection_text'],
                'browserMessage'    => $this->novalnetGateway->helper->oPlugin->oPluginSprachvariableAssoc_arr['__NN_redirection_browser_text'],
                'buttonText'        => $this->novalnetGateway->helper->oPlugin->oPluginSprachvariableAssoc_arr['__NN_redirection_button_text'],
                'shopLatest'        => $shopVersion == '4x',
                'paymentMethodPath' => $shopUrl . '/' . PFAD_PLUGIN . $this->novalnetGateway->helper->oPlugin->cVerzeichnis . '/' . PFAD_PLUGIN_VERSION . $this->novalnetGateway->helper->oPlugin->nVersion . '/' . PFAD_PLUGIN_PAYMENTMETHOD
            ));

            return false;
        } else {
            $paymentRequestParameters['payment_ref'] = $this->novalnetGateway->helper->getPaymentReferenceValues($this->paymentName, 'nNntid');
        }

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
        if (!empty($_SESSION[$this->paymentName]['one_click_shopping'])) {
            // Condition to check process reference payment call
            // Do server call when payment before order completion option is set to 'Ja'
            $this->novalnetGateway->performServerCall($this->paymentName);
        }

        return $this->novalnetGateway->verifyNotification($order, $this->paymentName, $this->paymentKey, empty($_SESSION[$this->paymentName]['one_click_shopping']) ? $args : ''); // Finalises the order based on response
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

        $argsArray = array('tid_status' => $_SESSION[$this->paymentName]['tid_status'] ? $_SESSION[$this->paymentName]['tid_status'] : $args['tid_status'], 'tid' => $_SESSION[$this->paymentName]['tid'] ? $_SESSION[$this->paymentName]['tid'] : $args['tid']); // Forms response parameters either from session or the $args value

       
        $incomingPayment = $DB->$selectQuery('tzahlungseingang', 'kBestellung', $order->kBestellung, 'cHinweis', $argsArray['tid']); // Verify if the payment has been received already for the transaction

        if (is_object($incomingPayment) && intval($incomingPayment->kZahlungseingang) > 0) {
            $this->novalnetGateway->completeProcess($order, $this->generateHash($order), $this->paymentName, $args);
            // Verifies if the payment has not been processed already
        } else {
            // Adds the payment method into the shop and changes the order status
            $this->updateShopDatabase($order, $argsArray);

            $this->novalnetGateway->handlePaymentCompletion($order, $this->generateHash($order), $this->paymentKey, $this->paymentName, empty($_SESSION[$this->paymentName]['one_click_shopping']) ? $args : ''); // Completes the order
        }
    }

    /**
     * Adds the payment method into the shop, updates notification ID, sets order status
     *
     * @param object $order
     * @param array  $argsArray
     * @return none
     */
    public function updateShopDatabase($order, $argsArray)
    {
        if ($argsArray['tid_status'] == 100 && $this->novalnetGateway->helper->getConfigurationParams('extensive_option', $this->paymentName) != '2') {// Adds to incoming payments only if the status is 100
            $incomingPayment           = new stdClass();
            $incomingPayment->fBetrag  = $order->fGesamtsummeKundenwaehrung;
            $incomingPayment->cISO     = $order->Waehrung->cISO;
            $incomingPayment->cHinweis = $argsArray['tid'];
            $this->name                = $order->cZahlungsartName;  // Retrieves and assigns payment name to the payment method object
            $this->addIncomingPayment($order, $incomingPayment); // Adds the current transaction into the shop's order table

            NovalnetGateway::performDbExecution('tbestellung', 'dBezahltDatum = now()', 'cBestellNr = "' .$order->cBestellNr . '"'); // Updates the value into the database
        }

        $this->updateNotificationID($order->kBestellung, $argsArray['tid']);                                            // Updates transaction ID into shop for reference
        NovalnetGateway::performDbExecution('tbestellung', 'cStatus=' . constant(($argsArray['tid_status']==85) ? $this->novalnetGateway->helper->getConfigurationParams('confirm_order_status') : (in_array($argsArray['tid_status'], array(90)) ? $this->novalnetGateway->helper->getConfigurationParams('pending_status', $this->paymentName) : $this->novalnetGateway->helper->getConfigurationParams('set_order_status', $this->paymentName))), 'cBestellNr = "' . $order->cBestellNr . '"'); // Updates the value into the database
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
        $handlerUrlParameters['cReturnURL']= ($_SESSION['Zahlungsart']->nWaehrendBestellung == 0) ? $this->getNotificationURL($orderHash) : $this->getNotificationURL($orderHash).'&sh=' . $orderHash;
        $handlerUrlParameters['cFailureURL']=$handlerUrlParameters['cReturnURL'];

        return $handlerUrlParameters;
    }
}
