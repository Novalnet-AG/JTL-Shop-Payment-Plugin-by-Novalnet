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
 * Script: NovalnetCreditcard.class.php
 *
 */
require_once(PFAD_ROOT . PFAD_INCLUDES_MODULES . 'PaymentMethod.class.php');
require_once('NovalnetGateway.class.php');

/**
 * Class NovalnetCreditcard
 */
class NovalnetCreditcard extends PaymentMethod
{
    /**
     * @var string
     */
    public $moduleID;

    /**
     * @var string
     */
    public $paymentName = 'novalnet_cc';

    /**
     * @var string
     */
    public $paymentKey = 6;

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
    public $novalnetPciPayportUrl = 'https://payport.novalnet.de/pci_payport';

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
        $this->moduleID = 'kPlugin_' . $this->novalnetGateway->helper->oPlugin->kPlugin . '_novalnetkreditkarte';

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

        $this->name    = 'Novalnet Kreditkarte';
        $this->caption = 'Novalnet Kreditkarte';

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
        $this->novalnetGateway->setupShopInformationText($this->moduleID, 'creditcard');

        return !(!$this->novalnetGateway->helper->getConfigurationParams('enablemode', $this->paymentName)
            || $this->novalnetGateway->isConfigInvalid());
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

        $this->novalnetGateway->helper->novalnetSessionUnset($this->paymentName);  // Unsets the other Novalnet payment sessions

        $referenceTid = $this->novalnetGateway->helper->getPaymentReferenceValues($this->paymentName, 'nNntid'); // Gets reference TID for reference transaction

        $placeholder = array(
                            '__NN_credit_card_name',
                            '__NN_credit_card_number',
                            '__NN_credit_card_date',
                            '__NN_credit_card_month',
                            '__NN_credit_card_year',
                            '__NN_testmode',
                            '__NN_zero_booking_note',
                            '__NN_card_details_link_old',
                            '__NN_card_details_link_new',
                            '__NN_credit_card_desc',
                            '__NN_credit_card_type',
                            '__NN_redirection_text',
                            '__NN_redirection_browser_text',
                            '__NN_oneclick_cc_save_data'
                        );

        $smarty->assign(array(
            'templateFile'       => PFAD_ROOT . PFAD_PLUGIN . $this->novalnetGateway->helper->oPlugin->cVerzeichnis . '/' . PFAD_PLUGIN_VERSION . $this->novalnetGateway->helper->oPlugin->nVersion . '/' . PFAD_PLUGIN_PAYMENTMETHOD . 'template/'
            . $shopVersion . '/cc.tpl',
            'payment_name'       => $this->paymentName,
            'testMode'           => $this->novalnetGateway->helper->getConfigurationParams('testmode', $this->paymentName),
            'zeroBooking'        => ($this->novalnetGateway->helper->getConfigurationParams('extensive_option', $this->paymentName) == '2'),
            'paymentMethodPath'  => $shopUrl . '/' . PFAD_PLUGIN . $this->novalnetGateway->helper->oPlugin->cVerzeichnis . '/' . PFAD_PLUGIN_VERSION . $this->novalnetGateway->helper->oPlugin->nVersion . '/' . PFAD_PLUGIN_PAYMENTMETHOD,
            'formError'          => !empty($_SESSION[$this->paymentName]['form_error'])
                                        ? $_SESSION[$this->paymentName]['form_error'] : '',
            'formIdentifier'     => $this->getCreditCardFormIdentifier(),      // Generates and returns the shop secured form identifier
            'nnLang'             => nnGetLanguageText($placeholder),          // Get language texts for the variables
            'creditcardFields'   => $this->getDynamicCreditCardFormFields(),  // Retrieves the Credit Card style and texts
            'shopLanguage'       => nnGetShopLanguage(),                      // Get current shop language
            'cc3dactive'         => $this->is3dSecureEnabled()               // Verify CC3d options are active
        ));

        if ($this->novalnetGateway->helper->getConfigurationParams('extensive_option', $this->paymentName) == '1' &&  (!($this->novalnetGateway->helper->getConfigurationParams('cc3d_active_mode') || $this->novalnetGateway->helper->getConfigurationParams('cc3d_fraud_check')))) { // Condition to display saved card details
             $smarty->assign('one_click_shopping',true);
         
           if(!empty($referenceTid)) {
               $smarty->assign(
                'nn_saved_details',
                unserialize(
                    $this->novalnetGateway->helper->getPaymentReferenceValues($this->paymentName, 'cAdditionalInfo')
                ));
            }         
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
        $paymentRequestParameters = $this->novalnetGateway->generatePaymentParams($order, $this->paymentName);  // Retrieves payment parameters for the transaction

        $paymentRequestParameters['key'] = $this->paymentKey;
        $paymentRequestParameters['payment_type'] = 'CREDITCARD';

        $extensiveOption=$this->novalnetGateway->helper->getConfigurationParams('extensive_option', $this->paymentName);

        if ($extensiveOption == '2') { // Condition to check zero amount booking
            unset($paymentRequestParameters['on_hold']); // Restrict keeping the booking payment as on-hold
            $_SESSION['nn_booking'] = $paymentRequestParameters;
        }

        if (empty($_SESSION[$this->paymentName]['one_click_shopping'])) { // Condition to check reference transaction
            if (($extensiveOption == '1' && ($_SESSION[$this->paymentName]['nn_save_payment']) && !($this->novalnetGateway->helper->getConfigurationParams('cc3d_active_mode')
                    && $this->novalnetGateway->helper->getConfigurationParams('cc3d_fraud_check')))
                    || $extensiveOption == '2') {
                $paymentRequestParameters['create_payment_ref'] = 1;
            }

            $paymentRequestParameters['nn_it']       = 'iframe';
            $paymentRequestParameters['pan_hash']    = $_SESSION[$this->paymentName]['nn_cc_hash'];
            $paymentRequestParameters['unique_id']   = $_SESSION[$this->paymentName]['nn_cc_uniqueid'];
        } else { // If the credit card payment needs to be processed as a reference payment
            $paymentRequestParameters['payment_ref'] = $this->novalnetGateway->helper->getPaymentReferenceValues(
                $this->paymentName,
                'nNntid'
            );
        }

        $this->novalnetGateway->preValidationCheckOnSubmission($paymentRequestParameters, $order, $this->paymentName);  // Validates whether the transaction can be passed to the server

        // If Credit Card 3D Secure or Credit Card fraud check is enabled
        if ($this->is3dSecureEnabled()) {
            $handlerUrlParameters = $this->getPaymentReturnUrls($orderHash, $order->kBestellung);  // Retrieves return URL's for redirection payment

            if ($this->novalnetGateway->helper->getConfigurationParams('cc3d_active_mode')) {
                $paymentRequestParameters['cc_3d']           = 1;
            }

            $paymentRequestParameters['return_url']          = $handlerUrlParameters['cReturnURL'];
            $paymentRequestParameters['return_method']       = 'POST';
            $paymentRequestParameters['error_return_url']    = $handlerUrlParameters['cFailureURL'];
            $paymentRequestParameters['error_return_method'] = 'POST';
            $paymentRequestParameters['session']             = session_id();
            $paymentRequestParameters['uniqid']              = $uniqid;
            $paymentRequestParameters['implementation']      = 'ENC';

            $this->novalnetGateway->helper->generateEncodeArray($paymentRequestParameters, $uniqid);
            $paymentRequestParameters['hash'] = $this->novalnetGateway->helper->generateHashValue($paymentRequestParameters, $this->paymentKey); // Encodes the basic payment parameters before sending to third party
            
            $smarty->assign(array(
                'paymentUrl'        => $this->novalnetPciPayportUrl,
                'datas'             => $paymentRequestParameters,
                'message'           => $this->novalnetGateway->helper->oPlugin->oPluginSprachvariableAssoc_arr['__NN_redirection_text'],
                'browserMessage'    => $this->novalnetGateway->helper->oPlugin->oPluginSprachvariableAssoc_arr['__NN_redirection_browser_text'],
                'buttonText'        => $this->novalnetGateway->helper->oPlugin->oPluginSprachvariableAssoc_arr['__NN_redirection_button_text'],
                'shopLatest'        => $shopVersion == '4x',
                'paymentMethodPath' => $shopUrl . '/' . PFAD_PLUGIN .
                                        $this->novalnetGateway->helper->oPlugin->cVerzeichnis . '/' .
                                        PFAD_PLUGIN_VERSION . $this->novalnetGateway->helper->oPlugin->nVersion . '/' .
                                        PFAD_PLUGIN_PAYMENTMETHOD,
            ));

            return false;
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
     * Core function - Called on notification URL
     *
     * @param object $order
     * @param string $hash
     * @param array  $args
     * @return bool
     */
    public function finalizeOrder($order, $hash, $args)
    {
        if (!$this->is3dSecureEnabled()) {
            // Condition to check process reference payment call
            // Do server call when payment before order completion option is set to 'Ja'
            $this->novalnetGateway->performServerCall($this->paymentName);
        }

        return $this->novalnetGateway->verifyNotification(
            $order,
            $this->paymentName,
            $this->paymentKey,
            $this->is3dSecureEnabled() ? $args : ''
        );   // Finalises the order based on response
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

        // Forms response parameters either from session or the $args value
        $argsArray = array('tid_status' => $_SESSION[$this->paymentName]['tid_status'] ?
                                $_SESSION[$this->paymentName]['tid_status'] :
                                $args['tid_status'],
                           'tid' => $_SESSION[$this->paymentName]['tid'] ?
                                $_SESSION[$this->paymentName]['tid'] :
                                $args['tid']
                    );

       
        $incomingPayment = $DB->$selectQuery('tzahlungseingang', 'kBestellung', $order->kBestellung, 'cHinweis', $argsArray['tid']); // Verify if the payment has been received already for the transaction

        if (is_object($incomingPayment) && intval($incomingPayment->kZahlungseingang) > 0) {
            $this->novalnetGateway->completeProcess($order, $this->generateHash($order), $this->paymentName, $args);
            // Verifies if the payment has not been processed already
        } else {
            // Adds the payment method into the shop and changes the order status
            $this->updateShopDatabase($order, $argsArray);

             // Completes the order
            $this->novalnetGateway->handlePaymentCompletion(
                $order,
                $this->generateHash($order),
                $this->paymentKey,
                $this->paymentName,
                $this->is3dSecureEnabled() ? $args : ''
            );
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
        // Adds to incoming payments only if the status is 100 or completed
        if ($argsArray['tid_status'] == 100
            && $this->novalnetGateway->helper->getConfigurationParams('extensive_option', $this->paymentName) != '2') {
            $incomingPayment           = new stdClass();
            $incomingPayment->fBetrag  = $order->fGesamtsummeKundenwaehrung;
            $incomingPayment->cISO     = $order->Waehrung->cISO;
            $incomingPayment->cHinweis = $argsArray['tid'];
            $this->name                = $order->cZahlungsartName;  // Retrieves and assigns payment name to the payment method object
            $this->addIncomingPayment($order, $incomingPayment);    // Adds the current transaction into the shop's order table

            NovalnetGateway::performDbExecution('tbestellung', 'dBezahltDatum = now()', 'cBestellNr = "' .$order->cBestellNr . '"'); // Updates the value into the database
        }

        $this->updateNotificationID($order->kBestellung, $argsArray['tid']); // Updates transaction ID into shop for reference

        NovalnetGateway::performDbExecution('tbestellung', 'cStatus='.constant(($argsArray['tid_status']==98) ? $this->novalnetGateway->helper->getConfigurationParams('confirm_order_status') :$this->novalnetGateway->helper->getConfigurationParams('set_order_status', $this->paymentName)), 'cBestellNr = "' . $order->cBestellNr . '"'); // Updates the value into the database
    }

    /**
     * Generates and returns the shop secured form identifier
     *
     * @param none
     * @return none
     */
    public function getCreditCardFormIdentifier()
    {
        return base64_encode('vendor='.$this->novalnetGateway->helper->getConfigurationParams('vendorid').'&product='.$this->novalnetGateway->helper->getConfigurationParams('productid').'&server_ip='.nnGetIpAddress('SERVER_ADDR').'&lang='.nnGetShopLanguage());
    }

    /**
     * Retrieves Credit Card form style set in payment configuration and texts present in language files
     *
     * @param none
     * @return array
     */
    public function getDynamicCreditCardFormFields()
    {
        $ccformFields = array();

        $styleConfiguration = array('form_label', 'form_input', 'form_css');

        foreach($styleConfiguration as $value) {
            $ccformFields[$value] = $this->novalnetGateway->helper->getConfigurationParams($value, $this->paymentName);
        }

        $textFields = array( 'credit_card_name', 'credit_card_name_input', 'credit_card_number', 'credit_card_number_input', 'credit_card_date', 'credit_card_date_input', 'credit_card_cvc', 'credit_card_cvc_input', 'credit_card_cvc_hint', 'credit_card_error' );

        foreach($textFields as $value) {
            $ccformFields[$value] = utf8_encode($this->novalnetGateway->helper->oPlugin->oPluginSprachvariableAssoc_arr['__NN_' . $value]);
        }

        $encodedFormFields = json_encode($ccformFields);

        return ($encodedFormFields === null && json_last_error() !== JSON_ERROR_NONE) ? '' : $encodedFormFields;
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
            $handlerUrlParameters['cReturnURL'] = $this->getNotificationURL($orderHash);
            $handlerUrlParameters['cFailureURL'] = $handlerUrlParameters['cReturnURL'];

            return $handlerUrlParameters;
        }

        $handlerUrlParameters['cReturnURL'] = $this->getNotificationURL($orderHash) . '&sh=' . $orderHash;
        $handlerUrlParameters['cFailureURL'] = $handlerUrlParameters['cReturnURL'];

        return $handlerUrlParameters;
    }

    /**
     * Verifies if the Credit Card 3D secure related options are enabled
     *
     * @param none
     * @return bool
     */
    public function is3dSecureEnabled()
    {
        return $this->novalnetGateway->helper->getConfigurationParams('cc3d_active_mode') ||
                    $this->novalnetGateway->helper->getConfigurationParams('cc3d_fraud_check');
    }
}
