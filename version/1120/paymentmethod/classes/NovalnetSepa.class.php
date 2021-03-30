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
    public $moduleID;

    /**
     * @var string
     */
    public $paymentName = 'novalnet_sepa';

    /**
     * @var string
     */
    public $paymentKey = 37;

    /**
     * @var string
     */
    public $paymentGuaranteeKey = 40;

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
        $this->moduleID = 'kPlugin_' . $this->novalnetGateway->helper->oPlugin->kPlugin . '_novalnetlastschriftsepa';

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
        // Update necessary for the shop version greater than 406 where shop information text is empty by default
        $this->novalnetGateway->setupShopInformationText($this->moduleID, 'sepa');

        return !((isset($_SESSION[$this->paymentName . '_invalid']) &&
                (time() < $_SESSION[$this->paymentName . '_time_limit']))
                || !$this->novalnetGateway->helper->getConfigurationParams('enablemode', $this->paymentName) ||
                $this->novalnetGateway->isConfigInvalid());
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

        $referenceTid = $this->novalnetGateway->helper->getPaymentReferenceValues($this->paymentName, 'nNntid'); // Gets reference TID for reference transaction

        $this->novalnetGateway->checkGuaranteedPaymentOption($this->paymentName);

        if (empty($_SESSION['nn_' . $this->paymentName . '_guarantee'])) {
            $this->novalnetGateway->displayFraudCheck($this->paymentName);  // To display additional form fields for fraud prevention setup
        }

        $placeholder = array(
                            '__NN_sepa_holder_name',           
                            '__NN_sepa_account_number',
                            '__NN_guarantee_birthdate',
                            '__NN_sepa_error',
                            '__NN_sepa_mandate_text',
                            '__NN_sepa_mandate_instruction_one',
                            '__NN_sepa_mandate_instruction_two',
                            '__NN_sepa_mandate_instruction_three',
                            '__NN_sepa_description',
                            '__NN_javascript_error',
                            '__NN_callback_phone_number',
                            '__NN_callback_sms',
                            '__NN_callback_pin',
                            '__NN_callback_forgot_pin',
                            '__NN_callback_telephone_error',
                            '__NN_callback_mobile_error',
                            '__NN_callback_pin_error',
                            '__NN_callback_pin_error_empty',
                            '__NN_testmode',
                            '__NN_zero_booking_note',
                            '__NN_account_details_link_old',
                            '__NN_account_details_link_new',
                            '__NN_oneclick_sepa_save_data',
                            '__NN_birthdate_error',
                            '__NN_birthdate_valid_error'
                        );

        $affDetails = $this->novalnetGateway->helper->getAffiliateDetails();  // Get affiliate details for the current user

        $smarty->assign(array(
            'templateFile'        => PFAD_ROOT . PFAD_PLUGIN . $this->novalnetGateway->helper->oPlugin->cVerzeichnis . '/' . PFAD_PLUGIN_VERSION . $this->novalnetGateway->helper->oPlugin->nVersion . '/' . PFAD_PLUGIN_PAYMENTMETHOD . 'template/' . $shopVersion . '/sepa.tpl',
            'vendorId'            => empty($affDetails->vendorid) ? $this->novalnetGateway->vendor : $affDetails->vendorid,
            'authCode'            => empty($affDetails->cAffAuthcode) ? $this->novalnetGateway->auth_code : $affDetails->cAffAuthcode,
            'testMode'            => $this->novalnetGateway->helper->getConfigurationParams('testmode', $this->paymentName),
            'zeroBooking'         => ($this->novalnetGateway->helper->getConfigurationParams('extensive_option', $this->paymentName) == '2'),
            'guaranteeForce'      => $this->novalnetGateway->helper->getConfigurationParams('guarantee_force', $this->paymentName),
            'isPaymentGuarantee'  => !empty($_SESSION['nn_' . $this->paymentName . '_guarantee']) ? $_SESSION['nn_' . $this->paymentName . '_guarantee'] : '',
            'paymentMethodPath'   => $shopUrl . '/' . PFAD_PLUGIN . $this->novalnetGateway->helper->oPlugin->cVerzeichnis . '/' . PFAD_PLUGIN_VERSION . $this->novalnetGateway->helper->oPlugin->nVersion . '/' . PFAD_PLUGIN_PAYMENTMETHOD,
            'formError'           => !empty($_SESSION[$this->paymentName]['form_error']) ? $_SESSION[$this->paymentName]['form_error'] : '',
            'nnLang'              => nnGetLanguageText($placeholder), // Get language texts for the variables,
            'company'             => $_SESSION['Kunde']->cFirma,
            'remoteIp'            => nnGetIpAddress('REMOTE_ADDR')
        ));

        if ($this->novalnetGateway->helper->getConfigurationParams('extensive_option', $this->paymentName) == '1' && empty($_SESSION[$this->paymentName]['tid'])) { // Condition to check reference transaction
            $smarty->assign('one_click_shopping', true);
         }
         
         if(!empty($referenceTid)) {
            $smarty->assign(
                'nn_saved_details',
                unserialize($this->novalnetGateway->helper->getPaymentReferenceValues(
                    $this->paymentName,
                    'cAdditionalInfo'
                ))
            );
        }

        if (!$this->novalnetGateway->basicValidationOnhandleAdditional($this->paymentName)) { // Validation on displaying payment form before submission
            return false;
        } elseif (isset($aPost_arr['nn_payment'])) {        // Only pass the payment step if the payment has been set
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
     * @return none
     */
    public function preparePaymentProcess($order)
    {
        // Process when reordering the payment from My-account (for shop 3.x series)
        if (!empty($_REQUEST['kBestellung'])) {
            $this->novalnetGateway->handleReorderProcess($order);
        }

        $orderHash = $this->generateHash($order); // Core function - To generate order hash

        // Performs second call for fraud module after payment call success
        if (!empty($_SESSION['nn_' . $this->paymentName . '_tid'])) {
            $this->novalnetGateway->doSecondCall($order, $this->paymentName);   // Fraud module xml call to complete the order
            header('Location:' . $this->getNotificationURL($orderHash) . '&sh=' . $orderHash);
            exit;
        } else {
            $paymentRequestParameters = $this->novalnetGateway->generatePaymentParams($order, $this->paymentName);  // Retrieves payment parameters for the transaction

            $paymentRequestParameters['key']          = $this->paymentKey;
            $paymentRequestParameters['payment_type'] = 'DIRECT_DEBIT_SEPA';

            $extensiveOption = $this->novalnetGateway->helper->getConfigurationParams('extensive_option', $this->paymentName);
            // Check to find whether the payment should be processed as a guaranteed payment
            if (!empty($this->novalnetGateway->helper->getConfigurationParams('guarantee', $this->paymentName))) {
                $paymentRequestParameters['key']          = $this->paymentGuaranteeKey;
                $paymentRequestParameters['payment_type'] = 'GUARANTEED_DIRECT_DEBIT_SEPA';
                $paymentRequestParameters['birth_date']   = date('Y-m-d', strtotime($_SESSION[$this->paymentName]['nn_dob']));
            } elseif ($extensiveOption == '2') { // Condition to check zero amount booking
                unset($paymentRequestParameters['on_hold']); // Restrict keeping the booking payment as on-hold
                $_SESSION['nn_booking'] = $paymentRequestParameters;
            }

            if (!empty($_SESSION[$this->paymentName]['one_click_shopping'])) { // Condition to reference transaction
                $paymentRequestParameters['payment_ref'] = $this->novalnetGateway->helper->getPaymentReferenceValues($this->paymentName, 'nNntid');
            } else {
                if (($extensiveOption == '1' && ($_SESSION[$this->paymentName]['nn_save_payment'])) || ($extensiveOption == '2' &&
                    empty($_SESSION['nn_' . $this->paymentName . '_guarantee']))) {
                        $paymentRequestParameters['create_payment_ref'] = 1;
                }
                $paymentRequestParameters['bank_account_holder'] = $_SESSION[$this->paymentName]['nn_sepaowner'];
                $paymentRequestParameters['iban'] = $_SESSION[$this->paymentName]['nn_sepa_account_no'];
            }

            $paymentRequestParameters['sepa_due_date'] = self::getSepaDuedate($this->novalnetGateway->helper->getConfigurationParams('sepa_due_date')); // Retrieves sepa due date from configuration

            $this->novalnetGateway->preValidationCheckOnSubmission($paymentRequestParameters, $order, $this->paymentName); // Validates whether the transaction can be passed to the server

            $_SESSION['nn_request'] = $paymentRequestParameters;

            if ($this->duringCheckout == 0) {
                // Do server call when payment before order completion option is set to 'Nein'
                $this->novalnetGateway->performServerCall($this->paymentName);
                // Finalises the order based on response
                $this->novalnetGateway->verifyNotification(
                    $order,
                    $this->paymentName,
                    !empty($_SESSION['nn_' . $this->paymentName . '_guarantee']) ?
                        $this->paymentGuaranteeKey :
                        $this->paymentKey
                );

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
        // Condition to check whether the first payment call has been processed already
        if (empty($_SESSION['nn_' . $this->paymentName . '_tid'])) {
            // Do server call when payment before order completion option is set to 'Ja'
            $this->novalnetGateway->performServerCall($this->paymentName);
            if ($this->novalnetGateway->helper->getConfigurationParams('pin_by_callback', $this->paymentName) != '0' && $_SESSION[$this->paymentName]['status'] == 100) { // Setup fraud module values for second call and redirects to payment page to enter pin
                $this->novalnetGateway->setupFraudModuleValues($this->paymentName, $order->fGesamtsumme);
            }
        }

        return $this->novalnetGateway->verifyNotification(
            $order,
            $this->paymentName,
            !empty($_SESSION['nn_' . $this->paymentName . '_guarantee']) ?
                $this->paymentGuaranteeKey :
                $this->paymentKey
        );
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
        if ($_SESSION[$this->paymentName]['tid_status'] == 100 && $this->novalnetGateway->helper->getConfigurationParams('extensive_option', $this->paymentName) != '2') { // Adds to incoming payments only if the status is 100 or completed
            $incomingPayment           = new stdClass();
            $incomingPayment->fBetrag  = $order->fGesamtsummeKundenwaehrung;
            $incomingPayment->cISO     = $order->Waehrung->cISO;
            $incomingPayment->cHinweis = $_SESSION[$this->paymentName]['tid'];
            $this->name                = $order->cZahlungsartName; // Retrieves and assigns payment name to the payment method object
            $this->addIncomingPayment($order, $incomingPayment);   // Adds the current transaction into the shop's order table

            NovalnetGateway::performDbExecution('tbestellung', 'dBezahltDatum = now()', 'cBestellNr = "' .$order->cBestellNr . '"'); // Updates the value into the database
        }

        $this->updateNotificationID($order->kBestellung, $_SESSION[$this->paymentName]['tid']);                         // Updates transaction ID into shop for reference

        NovalnetGateway::performDbExecution('tbestellung', 'cStatus='.constant(($_SESSION[$this->paymentName]['tid_status'] == 99) ? $this->novalnetGateway->helper->getConfigurationParams('confirm_order_status') : ((!empty($_SESSION['nn_' . $this->paymentName . '_guarantee']) && $_SESSION[$this->paymentName]['tid_status'] == 75) ? $this->novalnetGateway->helper->getConfigurationParams('guarantee_pending_status', $this->paymentName) : $this->novalnetGateway->helper->getConfigurationParams('set_order_status', $this->paymentName))), 'cBestellNr = "' . $order->cBestellNr . '"');// Updates the value into the database
    }

    /**
     * To get the Novalnet SEPA duedate in days
     *
     * @param integer $dueDate
     * @return date
     */
    public static function getSepaDuedate($dueDate)
    {
        return (nnIsDigits($dueDate) && $dueDate > 1) ?
            date('Y-m-d', strtotime('+' . $dueDate . 'days')) :
            date('Y-m-d', strtotime('+2 days'));
    }
}
