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
 * Script : class.NovalnetValidation.php
 *
 */

require_once('class.Novalnet.php');

class NovalnetValidation extends NovalnetGateway
{
    /**
     *
     * Constructor
     *
     */
    public function __construct()
    {
        $this->doAssignConfigVarsToMembers();
    }

    /**
     * Error set on basic param validation
     *
     * @param obj  $obj
     * @param obj  $order
     * @return none
     */
    public function basicValidation($obj, $order)
    {
        $this->basicParamValidation($obj, $order);

        if (!empty($_SESSION['novalnet']['error'])) {
            $novalnetInterface = new NovalnetInterface();
            header( 'Location:' . Shop::getURL() . '/bestellvorgang.php?editZahlungsart=1' );
            exit();
        }
    }

    /**
     * Basic configuration validation
     *
     * @param object $obj
     * @return bool
     */
    public function isConfigInvalid($obj)
    {
        if ((isset($obj->vendorid) && !self::isDigits($obj->vendorid)) || (isset($obj->productid) && !self::isDigits($obj->productid)) || (isset($obj->authcode) && empty($obj->authcode)) || (isset($obj->tariffid) && !self::isDigits($obj->tariffid)) || (isset($obj->key_password) && empty($obj->key_password))) {
            return true;
        }
    }

    /**
     * Validation for basic parameter values
     *
     * @param object $obj
     * @param object $order
     * @return none
     */
    public function basicParamValidation($obj, $order)
    {
        $oPlugin = NovalnetGateway::getPluginObject();

        if ($this->isConfigInvalid($obj) || self::isSubscriptionInvalid($obj)) {
            $_SESSION['novalnet']['error'] = html_entity_decode($oPlugin->oPluginSprachvariableAssoc_arr['__NN_merchant_error']);
        }

        if ((in_array($obj->paymentName,$obj->invoicePayments))) {
            if (!$obj->payment_reference1 && !$obj->payment_reference2 && !$obj->payment_reference3) {
                $_SESSION['novalnet']['error'] = html_entity_decode($oPlugin->oPluginSprachvariableAssoc_arr['__NN_invoice_payments_reference_error']);
            }
        }
        
        $this->wawiForCancelledOrder($order);
        
        return true;
    }

    /**
     * Subscription parameter validation
     *
     * @param object $obj
     * @return bool
     */
    public function isSubscriptionInvalid($obj)
    {
        if ((!empty($obj->tariff_period2) && !self::isDigits($obj->tariff_period2_amount)) || (self::isDigits($obj->tariff_period2_amount) && empty($obj->tariff_period2))){
            return true;
        }
    }

    /**
     * Validation for basic params on form payments
     *
     * @param string $obj
     * @@param object $order
     * @return bool
     */
    public function basicValidationOnhandleAdditional($obj, $order = array())
    {
        $oPlugin = NovalnetGateway::getPluginObject();

        $this->basicParamValidation($obj, $order);

        if ($obj->paymentName == 'novalnet_sepa') {
            if ((!empty($obj->sepa_due_date) && (!self::isDigits($obj->sepa_due_date) || $obj->sepa_due_date < 7)) || ( $obj->sepa_due_date == '0' && strlen($obj->sepa_due_date) > 0 )) {
                $_SESSION['novalnet']['error'] = html_entity_decode($oPlugin->oPluginSprachvariableAssoc_arr['__NN_sepa_duedate_error']);
            }
        } elseif ($obj->manual_check_limit && !self::isDigits($obj->manual_check_limit)) {
                $_SESSION['novalnet']['error'] = html_entity_decode($oPlugin->oPluginSprachvariableAssoc_arr['__NN_merchant_error']);
        }

        if (!empty($_SESSION['novalnet']['error'])) {
            $this->wawiForCancelledOrder($order);
            $this->assignError($_SESSION['novalnet']['error']);
            NovalnetInterface::returnOnError();
        }
        return true;
    }

    /**
     * Error assigning to template for display
     *
     * @param string $error
     * @return bool
     */
    public function assignError($error)
    {
        Shop::Smarty()->assign(array('error' => true, 'error_desc' => $error));
        return false;
    }

    /**
     * To check customer mail and name
     *
     * @param array $data
     * @param object $order
     * @return none
     */
    public function validateCustomerParameters($data, $order)
    {
        if (empty($data['email']) || (empty($data['first_name']) && empty($data['last_name'])) || !valid_email($data['email'])) {
            global $oPlugin;
            $_SESSION['novalnet']['error'] = html_entity_decode($oPlugin->oPluginSprachvariableAssoc_arr['__NN_customer_details_error']);
            $this->wawiForCancelledOrder($order);
            $novalnetInterface = new NovalnetInterface();
            header( 'Location:' . Shop::getURL() . '/bestellvorgang.php?editZahlungsart=1' );
            exit();
        }
    }

    /**
     * To check whether fraud prevention can be enabled or not
     *
     * @param object $obj
     * @return bool
     */
    public function isValidFraudCheck($obj)
    {
        $helper = new WarenkorbHelper();
        $basket = $helper->getTotal();

        return !((isset($obj->pin_amount) && $obj->pin_amount > 0 && (($basket->total[WarenkorbHelper::GROSS] * 100 ) < $obj->pin_amount)) || (!in_array($_SESSION['Kunde']->cLand, array('DE','AT','CH'))) || $_SESSION['Zahlungsart']->nWaehrendBestellung == 0);
    }

    /**
     * To check whether an element is digit
     *
     * @param string $element
     * @return bool
     */
    public static function isDigits($element)
    {
        return (preg_match('/^[0-9]+$/', $element));
    }

    /**
     * Update the Pickup by WAWI status for Cancelled order to prevent the orders from being picked up in WAWI
     *
     * @param object $order
     * @return none
     */
    public function wawiForCancelledOrder($order = array())
    {
        if (!empty($order) && !empty($_SESSION['novalnet']['error']) && $_SESSION['Zahlungsart']->nWaehrendBestellung == 0) {

            global $oPlugin;

            Shop::DB()->query('UPDATE tbestellung SET cStatus= "' . constant($oPlugin->oPluginEinstellungAssoc_arr['cancel_order_status']) . '", cAbgeholt="Y", kZahlungsart="' .$order->kZahlungsart .'" WHERE cBestellNr="' . $order->cBestellNr.'"', 4);
        }
        
        return true;
    }
}
