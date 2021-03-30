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
 * Script : novalnet_prepayment.class.php
 *
 */

require_once( 'class.NovalnetInterface.php' );

class novalnet_prepayment extends NovalnetInterface
{
    public $paymentName = 'novalnet_prepayment';

    /**
     *
     * Constructor
     *
     */
    function __construct()
    {
        $this->doAssignConfigVarsToMembers( $this->paymentName );
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

        $novalnetValidation = new NovalnetValidation();

        $placeholder = array('__NN_testmode','__NN_invoice_description');

        Shop::Smarty()->assign( array(
            'payment_name' => !empty($aPost_arr['Zahlungsart']) ? NovalnetGateway::getPaymentName($aPost_arr['Zahlungsart']) : '',
            'test_mode'    => ($this->testmode == '1'),
            'nn_lang'      => NovalnetGateway::getLanguageText($placeholder))
        );

        if (!$novalnetValidation->basicValidationOnhandleAdditional($this)) {
            return false;
        } elseif (isset($aPost_arr['nn_payment'])) {
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

        $this->novalnetSessionUnset($this->paymentName);
        $sessionHash = $this->generateHash( $order );

        if ($_SESSION['Zahlungsart']->nWaehrendBestellung == 0) {
             $_SESSION['nn_during_order'] = TRUE;
             $this->doPaymentCall($order);
             header( 'Location:' . $this->getNotificationURL( $sessionHash ) . '&ph=' .$sessionHash );
             exit();
        } else {
            header( 'Location:' . $this->getNotificationURL( $sessionHash ) . '&sh=' . $sessionHash );
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
        return parent::verifyNotification($order, $_SESSION['novalnet']['success']);
    }
}
