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
 * Script : novalnet_invoice.class.php
 *
 */

require_once( 'class.NovalnetInterface.php' );

class novalnet_invoice extends NovalnetInterface
{
    public $paymentName = 'novalnet_invoice';

    /**
     *
     * Constructor
     *
     */
    function __construct()
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
        if (isset($_SESSION['novalnet']['fraud_module_active']))
            $this->orderAmountCheck($order->fGesamtsumme);
            
        if (isset($_SESSION['nn_during_order']))
            unset($_SESSION['nn_during_order']);    

        $sessionHash = $this->generateHash( $order );

        if (empty($_SESSION[$this->paymentName]['tid']) ) {
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
        $this->novalnetSessionUnset($this->paymentName);
        
        $novalnetValidation = new NovalnetValidation();

        $placeholder = array('__NN_callback_phone_number','__NN_callback_sms','__NN_callback_pin','__NN_callback_forgot_pin','__NN_callback_telephone_error','__NN_callback_mobile_error','__NN_callback_pin_error','__NN_callback_pin_error_empty','__NN_testmode','__NN_invoice_description');

        $formFields = NovalnetGateway::getLanguageText($placeholder);

        $this->displayFraudCheck();

        Shop::Smarty()->assign(array(
            'payment_name' => NovalnetGateway::getPaymentName($aPost_arr['Zahlungsart']),
            'nn_lang'      => $formFields,
            'test_mode'    => ($this->testmode == '1')));

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
