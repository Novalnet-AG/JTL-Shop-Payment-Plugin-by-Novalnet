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
 * Script : novalnet_sepa.class.php
 *
 */

require_once( 'class.NovalnetInterface.php' );

class novalnet_sepa extends NovalnetInterface
{
    public $paymentName = 'novalnet_sepa';

    /**
     *
     * Constructor
     *
     */
    public function __construct()
    {
        $this->doAssignConfigVarsToMembers();
        $this->setError();
    }

    /**
     * Initialise the Payment process
     *
     * @param object $order
     * @return none
     */
    function preparePaymentProcess($order)
    {
        global $oPlugin;

        if (isset($_SESSION['novalnet']['fraud_module_active']))
            $this->orderAmountCheck($order->fGesamtsumme);
            
        if (isset($_SESSION['nn_during_order']))
            unset($_SESSION['nn_during_order']);    

        $novalnetValidation = new NovalnetValidation();
        $sessionHash = $this->generateHash( $order ); 

        if (!$novalnetValidation->basicValidationOnhandleAdditional($this, $order)) {
            return false;
        }
        
        if (empty($_SESSION[$this->paymentName]['tid'])) {
            if ($_SESSION['Zahlungsart']->nWaehrendBestellung == 0) {
                $_SESSION['nn_during_order'] = TRUE;
                $this->doPaymentCall($order);
                header( 'Location:' . $this->getNotificationURL( $sessionHash ) . '&ph=' .$sessionHash );
                exit();
            } else {
                header( 'Location:' . $this->getNotificationURL( $sessionHash ) . '&sh=' . $sessionHash );
                exit();
            }
        } else {
            $this->doSecondCall($order);
            header('Location:' . $this->getNotificationURL($sessionHash) . '&sh=' . $sessionHash);
            exit();
        }
    }

    /**
     * To check whether the payment method can be displayed in the payment page
     *
     * @param array $args_arr
     * @return bool
     */
    function isValidIntern($args_arr = array())
    {
        return !($this->isPaymentEnabled($this->paymentName));
    }

    /**
     * To display the payment form
     *
     * @param object $aPost_arr
     * @return bool
     */
    function handleAdditional($aPost_arr)
    {
        if (!empty($_SESSION['novalnet']['mail']) && ($_SESSION['novalnet']['mail'] != $_SESSION['Kunde']->cMail)) {
            unset($_SESSION[$this->paymentName]['nn_sepapanhash']);
        }
        $_SESSION['novalnet']['mail'] = $_SESSION['Kunde']->cMail;

        $this->novalnetSessionUnset($this->paymentName);

        $oPlugin = NovalnetGateway::getPluginObject();

        $novalnetValidation = new NovalnetValidation();

        $placeholder = array('__NN_sepa_holder_name','__NN_sepa_country_name','__NN_sepa_account_number','__NN_sepa_bank_code','__NN_merchant_error','__NN_javascript_error','__NN_sepa_mandate_error','__NN_sepa_mandate_text','__NN_sepa_error','__NN_sepa_description','__NN_callback_phone_number','__NN_callback_sms','__NN_callback_pin','__NN_callback_forgot_pin','__NN_callback_telephone_error','__NN_callback_mobile_error','__NN_callback_pin_error','__NN_callback_pin_error_empty','__NN_testmode','__NN_sepa_country_error');

        $formFields = NovalnetGateway::getLanguageText($placeholder);

        if ( $this->displayFraudCheck() && isset($_SESSION[$this->paymentName]['tid'])) {
            return true;
        }

        list( $this->vendorid, $this->authcode ) = NovalnetGateway::getAffiliateDetails();

            Shop::Smarty()->assign(array(
                'payment_name'              => NovalnetGateway::getPaymentName($aPost_arr['Zahlungsart']),
                'filePath'                  => Shop::getURL() . '/' . PFAD_PLUGIN . $oPlugin->cVerzeichnis . '/' . PFAD_PLUGIN_VERSION . $oPlugin->nVersion . '/' . PFAD_PLUGIN_PAYMENTMETHOD,
                'country_list'              => gibBelieferbareLaender($_SESSION['Kunde']->kKundengruppe),
                'lang_code'                 => NovalnetGateway::getShopLanguage(),
                'vendor_id'                 => $this->vendorid,
                'auth_code'                 => $this->authcode,
                'uniq_sepa_value'           => $this->getRandomString(),
                'nn_lang'                   => $formFields,
                'test_mode'                  => $this->testmode == '1',
                'sepa_holder'               => $_SESSION['Kunde']->cVorname.' '.$_SESSION['Kunde']->cNachname,
                'panhash'                   => $this->getSepaRefillHash()));

        if (!$novalnetValidation->basicValidationOnhandleAdditional($this)) {
            return false;
        } elseif (isset($aPost_arr['payment'])) {
            $formArray = array_map('trim', $aPost_arr);
            if (empty($_SESSION[$this->paymentName]['tid'])) {
                $_SESSION[$this->paymentName] = $formArray;
            } else {
                $_SESSION['post_array'] = $formArray;
            }
            return true;
        }
    }

    /**
     * Validates the payment form
     *
     * @param none
     * @return bool
     */
    public function validateAdditional()
    {
        return false;
    }

    /**
     * Process when notification url is handled
     *
     * @param object $order
     * @param string $hash
     * @param array $args
     * @return none
     */
    function handleNotification($order, $hash, $args)
    {
        $this->handleViaNotification($order, $_SESSION['novalnet']['success']);
    }

    /**
     * When order is finalized
     *
     * @param object $order
     * @param string $hash
     * @param array $args
     * @return bool
     */
    function finalizeOrder($order, $hash, $args)
    {
        return parent::verifyNotification($order);
    }
}
