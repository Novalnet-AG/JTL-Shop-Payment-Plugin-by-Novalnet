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
 * Script : novalnet_paypal.class.php
 *
 */

require_once( PFAD_ROOT . PFAD_INCLUDES_MODULES . 'PaymentMethod.class.php' );
require_once( 'class.Novalnet.php' );

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

		if (isset($_SESSION['novalnet']['nWaehrendBestellung']) && $_SESSION['novalnet']['nWaehrendBestellung'] == 'Nein') {
			if ((isset($_REQUEST['status']) && ( $_REQUEST['status'] == 100 || $_REQUEST['status'] == 90 ) && $_REQUEST['inputval3'] == $this->paymentName)){
				$this->orderComplete();
			}
		}

		if ($_REQUEST['status'] != 100 && !empty($_REQUEST['status_text']))
			$_SESSION['novalnet']['error'] = utf8_decode($_REQUEST['status_text']);
	}

	/**
	 * Initialise the Payment process
	 *
	 * @param object $order
	 * @return none
	 */
	function preparePaymentProcess($order)
	{
		if ($_SESSION['novalnet']['fraud_module_active'])
			$this->orderAmountCheck($order->fGesamtsumme);
			
		$this->doRedirectionCall($order, 'novalnetpaypal');
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
		$_SESSION['novalnet']['success'] = $_REQUEST;
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
		$_SESSION['novalnet']['success'] = $_REQUEST;
		return parent::verifyNotification($order, $hash, $args, $_SESSION['novalnet']['success']);
	}
}
