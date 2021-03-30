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
 * Script : novalnet_paypal.class.php
 *
 */

require_once( 'class.NovalnetInterface.php' );

class novalnet_paypal extends NovalnetInterface
{
    public $paymentName = 'novalnet_paypal';

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
     * Called when additional template is used
     *
     * @param array $aPost_arr
     * @return bool
     */
    public function handleAdditional($aPost_arr)
    {
        $this->novalnetSessionUnset($this->paymentName);

        $placeholder = array('__NN_testmode','__NN_redirection_text','__NN_redirection_browser_text');

        Shop::Smarty()->assign( array(
            'payment_name' => !empty($aPost_arr['Zahlungsart']) ? NovalnetGateway::getPaymentName($aPost_arr['Zahlungsart']) : '',
            'test_mode'    => ($this->testmode == '1'),
            'nn_lang'      => NovalnetGateway::getLanguageText($placeholder))
        );

        if (isset($aPost_arr['nn_payment'])) {
            return true;
        }
    }

    /**
     * Called when the additional template is submitted
     *
     * @param none
     * @return bool
     */
    public function validateAdditional()
    {
        return false;
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

        $this->doRedirectionCall($order);
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
     * Process when notification url is handled
     *
     * @param object $order
     * @param string $hash
     * @param array $args
     * @return none
     */
    function handleNotification($order, $hash, $args)
    {
        $this->handleViaNotification($order, $args);
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
        return parent::verifyNotification($order, $args);
    }
}
