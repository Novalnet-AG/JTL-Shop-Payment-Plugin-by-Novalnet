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
 * Script : class.Novalnet.php
 *
 */

require_once( PFAD_ROOT . PFAD_INCLUDES_MODULES . 'PaymentMethod.class.php' );

if (class_exists('NovalnetGateway')) {
    require_once('class.NovalnetInterface.php');
    require_once('class.NovalnetValidation.php');
}

class NovalnetGateway extends PaymentMethod
{
    public $redirectPayments = array('novalnet_banktransfer', 'novalnet_ideal', 'novalnet_paypal', 'novalnet_eps', 'novalnet_giropay', 'novalnet_cc');
    public $invoicePayments  = array('novalnet_invoice','novalnet_prepayment');
    public $paymentName;

    /**
     * Sets the payment key for the all the novalnet payments
     *
     * @param  string $paymentMethod
     * @return integer
     */
    public function setPaymentKey($paymentMethod = '')
    {
        $paymentMethod = empty($paymentMethod) ? $this->paymentName : $paymentMethod;
        $key = array(
            'novalnet_cc'           => 6,
            'novalnet_prepayment'   => 27,
            'novalnet_invoice'      => 27,
            'novalnet_banktransfer' => 33,
            'novalnet_paypal'       => 34,
            'novalnet_sepa'         => 37,
            'novalnet_ideal'        => 49,
            'novalnet_eps'          => 50,
            'novalnet_giropay'      => 69
        );
        return $key[$paymentMethod];
    }

    /**
     * Sets the payment redirection URL and method name
     *
     * @param  bool $paymentName
     * @return string
     */
    public function setPaymentConfiguration($paymentName = false)
    {
        $payment = array(
          'novalnet_prepayment'   => array('url' => 'https://payport.novalnet.de/paygate.jsp', 'name' => 'PREPAYMENT'),
          'novalnet_invoice'      => array('url' => 'https://payport.novalnet.de/paygate.jsp', 'name' => 'INVOICE'),
          'novalnet_cc'           => array('url' => 'https://payport.novalnet.de/pci_payport', 'name' => 'CREDITCARD'),
          'novalnet_paypal'       => array('url' => 'https://payport.novalnet.de/paypal_payport', 'name' => 'PAYPAL'),
          'novalnet_ideal'        => array('url' => 'https://payport.novalnet.de/online_transfer_payport', 'name' => 'IDEAL'),
          'novalnet_banktransfer' => array('url' => 'https://payport.novalnet.de/online_transfer_payport', 'name' => 'ONLINE_TRANSFER'),
          'novalnet_eps'          => array('url' => 'https://payport.novalnet.de/giropay', 'name' => 'EPS'),
          'novalnet_giropay'      => array('url' => 'https://payport.novalnet.de/giropay', 'name' => 'GIROPAY'),
          'novalnet_sepa'         => array('url' => 'https://payport.novalnet.de/paygate.jsp', 'name' => 'DIRECT_DEBIT_SEPA')
        );

        return ($paymentName ? $payment[$this->paymentName]['name'] : $payment[$this->paymentName]['url']);
    }

    /**
     * Assign the configuration values
     *
     * @param  none
     * @return none
     */
    public function doAssignConfigVarsToMembers()
    {
        global $oPlugin;

        $config = array('vendorid', 'productid', 'authcode', 'tariffid', 'tariff_period', 'tariff_period2_amount', 'tariff_period2','proxy','gateway_timeout', 'confirm_order_status', 'cancel_order_status', 'subscription_order_status', $this->paymentName . '_set_order_status',$this->paymentName . '_callback_status','paypal_pending_status',$this->paymentName . '_payment_reference1',$this->paymentName . '_payment_reference2', $this->paymentName . '_payment_reference3',$this->paymentName . '_reference1', $this->paymentName . '_reference2', $this->paymentName . '_enablemode',$this->paymentName . '_testmode','manual_check_limit','referrerid','key_password',$this->paymentName . '_pin_by_callback', $this->paymentName . '_pin_amount','callback_notify_url');

        if ($this->paymentName == 'novalnet_cc')
            array_push($config,'cc3d_active_mode','cc_amex_accept','cc_cartasi_accept','cc_maestro_accept');
        elseif ($this->paymentName == 'novalnet_sepa')
            array_push($config,'sepa_due_date','sepa_refill','sepa_autorefill');
        elseif ($this->paymentName == 'novalnet_invoice')
            $config[] = 'invoice_duration';

        foreach ($config as $configuration) {
                $val = (strpos($configuration,$this->paymentName) !== false) ? str_replace($this->paymentName.'_','',$configuration) : $configuration;

            if (isset($oPlugin->oPluginEinstellungAssoc_arr[$configuration]))
                $this->$val = trim($oPlugin->oPluginEinstellungAssoc_arr[$configuration]);
        }
    }

    /**
     * Set return urls for redirection payments
     *
     * @param  array $params
     * @param  array $order
     * @return none
     */
    public function setReturnUrls(&$params, $order)
    {
        $paymentHash = $this->generateHash($order);
        if ($_SESSION['Zahlungsart']->nWaehrendBestellung == 0) {
            $_SESSION['nn_during_order'] = TRUE;
            $params['cReturnURL'] =  $this->getNotificationURL($paymentHash) . '&ph=' . $paymentHash;
            $params['cFailureURL'] =  $this->getNotificationURL($paymentHash);
        } else {
            $params['cReturnURL'] = $params['cFailureURL'] = $this->getNotificationURL($paymentHash) . '&sh=' . $paymentHash;
        }
    }

    /**
     * Build basic parameters to server
     *
     * @param  array $data
     * @param  float $orderAmount
     * @return none
     */
    public function buildBasicParams(&$data, $orderAmount)
    {
        list ($this->vendorid, $this->authcode, $this->key_password) = $this->getAffiliateDetails();
        $amount = gibPreisString($orderAmount) * 100 ;
            $parameterKeys = $this->getBasicParametersNames();

            $data[$parameterKeys['authcode']]  = $this->authcode;
            $data[$parameterKeys['product']]   = $this->productid;
            $data[$parameterKeys['tariff']]    = $this->tariffid;
            $data['test_mode']  = $this->testmode;
            $data['amount']     = $amount;
            
        if (in_array($this->paymentName, $this->redirectPayments)) {
            $data['uniqid']     = uniqid();
            $data['hash']       = $this->generateHashValue($this->generateEncodeArray($data));
        }
        if( $this->doCheckManualCheckLimit( $amount ) == 1 )
               $data['on_hold'] = 1;

            $data[$parameterKeys['vendor']]  = $this->vendorid;
            $data['key']        = $this->setPaymentKey();
		
    }

    /**
     * Build common parameters to server
     *
     * @param  array $data
     * @param  string $currency
     * @param  array $customer
     * @return none
     */
    public function buildCommonParams(&$data, $currency, $customer)
    {
        $language                   = self::getShopLanguage();
        $data['currency']           = $currency;
        $data['remote_ip']          = (filter_var(getRealIp(), FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) || getRealIp() == '::1' )? '127.0.0.1' : getRealIp();
        $data['first_name']         = (!empty($customer->cVorname)) ? $customer->cVorname : $customer->cNachname;
        $data['last_name']          = (!empty($customer->cNachname)) ? $customer->cNachname : $customer->cVorname;
        $data['gender']             = 'u';
        $data['email']              = $customer->cMail;
        $data['street']             = $customer->cHausnummer . ',' . $customer->cStrasse;
        $data['search_in_street']   = 1;
        $data['city']               = $customer->cOrt;
        $data['zip']                = $customer->cPLZ;
        $data['language']           = $language;
        $data['lang']               = $language;
        $data['country_code']       = $customer->cLand;
        $data['country']            = $customer->cLand;
        $data['tel']                = !empty($this->pin_by_callback) && $this->pin_by_callback == 1 && !empty($_SESSION[$this->paymentName]['nn_telnumber']) ? $_SESSION[$this->paymentName]['nn_telnumber'] : $customer->cTel;
        $data['mobile']             = !empty($this->pin_by_callback) && $this->pin_by_callback == 2 && !empty($_SESSION[$this->paymentName]['nn_mob_number']) ? $_SESSION[$this->paymentName]['nn_mob_number'] : (($customer->cMobil != '') ? $customer->cMobil : '');
        $data['customer_no']        = !empty( $_SESSION['Kunde']->kKunde ) ? $_SESSION['Kunde']->kKunde : 'guest';
        $data['system_name']        = 'jtlshop';
        $data['system_version']     = $this->getFormattedVersion(intval(JTL_VERSION)) . '_NN_10.2.0';
        $data['system_url']         = Shop::getURL();
        $data['system_ip']          = (filter_var($_SERVER['SERVER_ADDR'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) || $_SERVER['SERVER_ADDR'] == '::1') ? '127.0.0.1' : $_SERVER['SERVER_ADDR'];
        $data['payment_type']       = $this->setPaymentConfiguration(true);
        if (!empty( $this->callback_notify_url ))
            $data['notify_url']     = $this->callback_notify_url;
        if(!empty( $_SESSION['Kunde']->cFirma ))
            $data['company']        = $_SESSION['Kunde']->cFirma;
    }

    /**
     * Build additional parameters to server
     *
     * @param  array $data
     * @param  array $params
     * @return none
     */
    public function buildAdditionalParams(&$data, $params)
    {
        $novalnetValidation = new NovalnetValidation();

        if (!empty($this->referrerid) && NovalnetValidation::isDigits($this->referrerid)) {
            $data['referrer_id']      = $this->referrerid;
        }

        if (!empty($this->tariff_period)) {
            $data['tariff_period']    = $this->tariff_period;
        }

        if (!empty($this->tariff_period2_amount) && NovalnetValidation::isDigits($this->tariff_period2_amount) && !empty($this->tariff_period2)) {
            $data['tariff_period2']   = $this->tariff_period2;
            $data['tariff_period2_amount']  = $this->tariff_period2_amount;
        }

        if (in_array($this->paymentName, $this->invoicePayments)) {
            $data['invoice_type']     = 'PREPAYMENT';
            
			if ( $_SESSION['Zahlungsart']->nWaehrendBestellung == 0 )
				$data['invoice_ref']     = 'BNR-'. $this->productid . '-' . $params['orderNo'];
			
            if ($this->paymentName == 'novalnet_invoice') {
                $data['invoice_type'] = 'INVOICE';
                $_SESSION['novalnet']['duedate'] = $this->getInvoiceDuedate();
                if (!empty($_SESSION['novalnet']['duedate']))
                    $data['due_date'] = $_SESSION['novalnet']['duedate'];
            }

        } elseif (in_array($this->paymentName,$this->redirectPayments)) {
            $data['session']            = session_id();
            $data['return_url']         = $params['cReturnURL'];
            $data['return_method']      = 'POST';
            $data['error_return_url']   = $params['cFailureURL'];
            $data['error_return_method']= 'POST';
            $data['user_variable_0']    =  Shop::getURL();
            $data['implementation']     = 'PHP';

            if($this->paymentName == 'novalnet_cc') {
                $data['implementation'] = 'PHP_PCI';
                unset($data['user_variable_0']);
                    if ($this->cc3d_active_mode)
                    $data['cc_3d'] = 1;
            }
        } elseif ($this->paymentName == 'novalnet_sepa') {
            $data['sepa_unique_id']     = $_SESSION[$this->paymentName]['nn_sepaunique_id'];
            $data['sepa_hash']          = $_SESSION[$this->paymentName]['nn_sepapanhash'];
            $data['bank_account_holder']= $_SESSION[$this->paymentName]['nn_sepaowner'];
            $data['iban_bic_confirmed'] = 1;
            $data['sepa_due_date']      = $this->getSepaDuedate();
        }

        if ($novalnetValidation->isValidFraudCheck($this)) {
            if (isset($this->pin_by_callback)) {
                if ($this->pin_by_callback == '1')
                    $data['pin_by_callback']  = 1;

                elseif ($this->pin_by_callback == '2')
                    $data['pin_by_sms'] = 1;
            }
        }
        if (!empty($this->reference1)) {
            $data['input1']     = 'reference1';
            $data['inputval1']  = strip_tags($this->reference1);
        }

        if (!empty($this->reference2)) {
            $data['input2']     = 'reference2';
            $data['inputval2']  = strip_tags($this->reference2);
        }

        if (!empty($params['orderNo']))
            $data['order_no']   = $params['orderNo'];

        $data['input3']         = 'payment';
        $data['inputval3']      = $this->paymentName;
    }

    /**
     * Unset the novalnet sessions
     *
     * @param string $payment
     * @return none
     */
    public function novalnetSessionUnset($payment)
    {
        $sessionArray = array('novalnet_sepa','novalnet_invoice');
        foreach ($sessionArray as $val) {
            if ($payment != $val) {
                unset($_SESSION[$val]);
            }
        }
    }

    /**
     * Build the Novalnet order comments
     *
     * @param array $parsed
     * @param array $order
     * @return string
     */
    public function updateOrderComments($parsed, $order)
    {
        $oPlugin = NovalnetGateway::getPluginObject();

        $comments = !empty( $_SESSION['kommentar'] ) ? $_SESSION['kommentar'] . PHP_EOL . PHP_EOL : '';

        if (isset($_SESSION[$this->paymentName]['tid'])) {
            $parsed['test_mode']        = $_SESSION[$this->paymentName]['test_mode'];
            $parsed['tid']              = $_SESSION[$this->paymentName]['tid'];
            $parsed['due_date']         = $_SESSION[$this->paymentName]['due_date'];
            $parsed['invoice_iban']     = $_SESSION[$this->paymentName]['invoice_iban'];
            $parsed['invoice_bic']      = $_SESSION[$this->paymentName]['invoice_bic'];
            $parsed['invoice_bankname'] = $_SESSION[$this->paymentName]['invoice_bankname'];
            $parsed['invoice_bankplace']= $_SESSION[$this->paymentName]['invoice_bankplace'];
            $parsed['amount']           = $_SESSION['novalnet']['amount'];
        }
            $parsed['product_id'] = $this->productid;

        if (in_array($this->paymentName, $this->redirectPayments)) {
            $parsed['test_mode'] = $this->generateDecode($parsed['test_mode']);
        }

        if ( !empty($parsed['test_mode']) || !empty($this->testmode) ) {
            $comments .= $oPlugin->oPluginSprachvariableAssoc_arr['__NN_test_order'] . PHP_EOL;
        }

        $comments .= $oPlugin->oPluginSprachvariableAssoc_arr['__NN_tid_label'] . $parsed['tid'] . PHP_EOL;

        if (in_array($this->paymentName,$this->invoicePayments)) {
            $comments .= $this->formInvoicePrepaymentComments($parsed , $order->Waehrung->cISO, $this->paymentName);
        }

        return $comments;
    }

    /**
     * Form invoice & prepayment payments comments
     *
     * @param array  $datas
     * @param string $currency
     * @param string $paymentMethod
     * @param bool   $updateAmount
     * @return string
     */
    public function formInvoicePrepaymentComments($datas = array(), $currency, $paymentMethod, $updateAmount = false)
    {
        $oPlugin = NovalnetGateway::getPluginObject();

        $datas = array_map('utf8_decode',$datas);

        $order_no = !empty($datas['order_no']) ? $datas['order_no'] : 'NN_ORDER';
        $duedate = new DateTime($datas['due_date']);
        $transComments  = '';
        $transComments .= PHP_EOL . $oPlugin->oPluginSprachvariableAssoc_arr['__NN_invoice_payments_comments'] . PHP_EOL;
        $transComments .= $oPlugin->oPluginSprachvariableAssoc_arr['__NN_invoice_duedate'] . $duedate->format('d.m.Y') . PHP_EOL;
        $transComments .= $oPlugin->oPluginSprachvariableAssoc_arr['__NN_invoice_payments_holder'] . PHP_EOL;
        $transComments .= 'IBAN: ' . $datas['invoice_iban'] . PHP_EOL;
        $transComments .= 'BIC: ' . $datas['invoice_bic'] . PHP_EOL;
        $transComments .= 'Bank: ' . $datas['invoice_bankname'] . ' ' . $datas['invoice_bankplace'] . PHP_EOL;
        $transComments .= $oPlugin->oPluginSprachvariableAssoc_arr['__NN_invoice_payments_amount'] . number_format( $datas['amount'], 2, ',', '' ) . ' ' . $currency . PHP_EOL;

        $referenceParams = $updateAmount ? unserialize( $datas['referenceValues'] ) : $this->getPaymentReferenceValues( $paymentMethod );
        $refCount = array_count_values( $referenceParams );
        $referenceSuffix = array( 'BNR-'. $datas['product_id'] . '-' . $order_no, 'TID ' . $datas['tid'], $oPlugin->oPluginSprachvariableAssoc_arr['__NN_order_number_text'] . $order_no );
        $i = 1;

        $transComments .= ( ( $refCount[1] > 1 ) ? $oPlugin->oPluginSprachvariableAssoc_arr['__NN_invoice_payments_multiple_reference_text'] : $oPlugin->oPluginSprachvariableAssoc_arr['__NN_invoice_payments_single_reference_text'] ) . PHP_EOL;

        foreach ( $referenceParams as $key => $val ) {

            if ( !empty( $val ) ) {
                $suffix = ( $_SESSION['cISOSprache'] == 'ger' && $refCount[1] > 1 ) ? $i . '. ' : ( $refCount[1] > 1 ? $i : '' );
                $transComments .= sprintf( $oPlugin->oPluginSprachvariableAssoc_arr['__NN_invoice_payments_reference'], $suffix ) . ': ' . $referenceSuffix[$key] . PHP_EOL;
                $i+=1;
            }
        }
        return $transComments;
    }

    /**
     * Retrieving payment reference parameters for invoice payments
     *
     * @param string $paymentMethod
     * @return array $params
     */
    public function getPaymentReferenceValues($paymentMethod)
    {
        $oPlugin = NovalnetGateway::getPluginObject();

        $paymentReference = array('payment_reference1','payment_reference2','payment_reference3');

        foreach ($paymentReference as $ref) {
            $params[] = $oPlugin->oPluginEinstellungAssoc_arr[$paymentMethod.'_'.$ref];

        }
        return $params;
    }

    /**
     * To update the order comments into database
     *
     * @param integer $order
     * @param string  $reference
     * @return none
     */
    public static function addReferenceToComment($order, $reference)
    {
        Shop::DB()->query("UPDATE tbestellung SET
        cKommentar = CONCAT(cKommentar, '" . $reference . "') WHERE kBestellung = ".$order,1);

        unset($_SESSION['kommentar']);
    }

    /**
     * Perform postback call to novalnet server
     *
     * @param array $parsed
     * @param string $orderNo
     * @param string $orderComments
     * @return none
     */
    public function postBackCall($parsed, $orderNo, $orderComments)
    {
        if (!empty($_SESSION['nn_aff_id'])) {
            list($this->vendorid,$this->authcode) = $this->getAffiliateDetails();
        }

        $postData = array(
            'vendor'      => $this->vendorid,
            'product'     => $this->productid,
            'tariff'      => $this->tariffid,
            'auth_code'   => $this->authcode,
            'key'         => $this->setPaymentKey(),
            'status'      => 100,
            'tid'         => (isset($parsed['tid']) && !empty($parsed['tid'])) ? $parsed['tid'] : $_SESSION[$this->paymentName]['tid'],
            'order_no'    => $orderNo
        );

        if (in_array($this->paymentName,$this->invoicePayments)) {
            $postData['invoice_ref'] = 'BNR-' . $postData['product']  . '-' . $orderNo;
            $orderComments = str_replace('NN_ORDER', $orderNo, $orderComments);
        }

        Shop::DB()->query('UPDATE tbestellung SET cKommentar = "' . $orderComments . '" WHERE cBestellNr ="' . $postData['order_no'] . '"', 1);
        $postData = http_build_query($postData, '', '&');
        $response = $this->sendCurlRequest( $postData, 'https://payport.novalnet.de/paygate.jsp' );
        unset( $_SESSION['novalnet'] );
    }

    /**
     * Finalize the order
     *
     * @param array  $order
     * @param string $paymentHash
     * @param array  $response
     * @return bool
     */
    public function verifyNotification($order, $response = array())
    {
        $oPlugin = NovalnetGateway::getPluginObject();

        if ( empty( $_SESSION[$this->paymentName]['tid'] ) && in_array($this->paymentName,array('novalnet_invoice','novalnet_prepayment','novalnet_sepa')) ) {
            $this->doPaymentCall($order);
        }

        if ( empty( $response ) ) {
            $response = $_SESSION['novalnet']['success'];
        }

        if ($response['status'] == 100 || ($this->paymentName == 'novalnet_paypal' && $response['status'] == 90)) {

            if (in_array($this->paymentName, $this->redirectPayments) && !$this->checkHash($response)) {
                $_SESSION['novalnet']['error'] = html_entity_decode($oPlugin->oPluginSprachvariableAssoc_arr['__NN_hash_error']);
                $response['status'] = $response['tid_status'] = 0;
                $this->returnOnError($order, $response); // Redirects to the error page
            }

            $_POST['kommentar'] = $this->updateOrderComments( $response, $order );
            unset($_SESSION['kommentar']);
            return true;
        } else {
            $_SESSION['novalnet']['error'] = utf8_decode($this->getResponseText($response));
            header( 'Location: ' . Shop::getURL() . '/bestellvorgang.php?editZahlungsart=1' );
            exit();
        }
    }

    /**
     * To insert the order details into novalnet tables
     *
     * @param array $response
     * @param integer $orderValue
     * @return none
     */
    public function insertOrderIntoDB($response, $orderValue)
    {
        $order = new Bestellung($orderValue);

        $tid = (isset($response['tid']) && !empty($response['tid'])) ? $response['tid'] : $_SESSION[$this->paymentName]['tid'];

        if (!empty($_SESSION['nn_aff_id'])) {
            list($this->vendorid, $this->authcode, $this->key_password) = $this->getAffiliateDetails();
        }

        $newLine = "<br/>";

        $insertOrder = new stdClass();
        $insertOrder->cNnorderid       = $order->cBestellNr;
        $insertOrder->cKonfigurations  = serialize(array('vendor' => $this->vendorid, 'auth_code' => $this->authcode, 'product' => $this->productid, 'tariff' => $this->tariffid, 'proxy' => $this->proxy));
        $insertOrder->nNntid           = $tid;
        $insertOrder->cZahlungsmethode = $this->paymentName;
        $insertOrder->cMail            = $_SESSION['Kunde']->cMail;
        $insertOrder->nStatuswert      = !empty($_SESSION[$this->paymentName]['tid_status']) ? $_SESSION[$this->paymentName]['tid_status'] : $response['tid_status'];
        $insertOrder->cKommentare      = $order->cKommentar . $newLine;
        $insertOrder->dDatum           = date('d.m.Y H:i:s') . $newLine;
        $insertOrder->cSepaHash        = ($this->paymentName == 'novalnet_sepa' && $_SESSION['Kunde']->nRegistriert != '0') ? $_SESSION[$this->paymentName]['nn_sepapanhash'] : '';
        $insertOrder->nBetrag          = gibPreisString($order->fGesamtsumme) * 100;

        Shop::DB()->insertRow('xplugin_novalnetag_tnovalnet_status', $insertOrder);

        if (!in_array($this->paymentName, $this->invoicePayments) && $response['status'] == 100) {

            $insertCallback = new stdClass();
            $insertCallback->cBestellnummer  = $insertOrder->cNnorderid;
            $insertCallback->dDatum          = date('Y-m-d H:i:s');
            $insertCallback->cZahlungsart    = $order->cZahlungsartName;
            $insertCallback->nReferenzTid    = $tid;
            $insertCallback->nCallbackAmount = $insertOrder->nBetrag;
            $insertCallback->cWaehrung       = isset($response['currency']) ? $response['currency'] : $_SESSION[$this->paymentName]['currency'];

            Shop::DB()->insertRow('xplugin_novalnetag_tcallback', $insertCallback);
        }

        if (in_array($this->paymentName, $this->invoicePayments)) {

            $insertInvoiceDetails = new stdClass();
            $insertInvoiceDetails->cBestellnummer   = $insertOrder->cNnorderid;
            $insertInvoiceDetails->nTid             = $tid;
            $insertInvoiceDetails->nProductId       = $this->productid;
            $insertInvoiceDetails->bTestmodus       = isset($response['test_mode']) ? $response['test_mode'] : $_SESSION[$this->paymentName]['test_mode'];
            $insertInvoiceDetails->cKontoinhaber    = 'NOVALNET AG';
            $insertInvoiceDetails->cKontonummer     = isset($response['invoice_account']) ? $response['invoice_account'] : $_SESSION[$this->paymentName]['invoice_account'];
            $insertInvoiceDetails->cBankleitzahl    = isset($response['invoice_bankcode']) ? $response['invoice_bankcode'] : $_SESSION[$this->paymentName]['invoice_bankcode'];
            $insertInvoiceDetails->cbankName        = isset($response['invoice_bankname']) ? $response['invoice_bankname'] : $_SESSION[$this->paymentName]['invoice_bankname'];
            $insertInvoiceDetails->cbankCity        = isset($response['invoice_bankplace']) ? $response['invoice_bankplace'] : $_SESSION[$this->paymentName]['invoice_bankplace'];
            $insertInvoiceDetails->nBetrag          = isset($response['amount']) ? $response['amount'] : $_SESSION['novalnet']['amount'];
            $insertInvoiceDetails->cWaehrung        = isset($response['currency']) ? $response['currency'] : $_SESSION[$this->paymentName]['currency'];
            $insertInvoiceDetails->cbankIban        = isset($response['invoice_iban']) ? $response['invoice_iban'] : $_SESSION[$this->paymentName]['invoice_iban'];
            $insertInvoiceDetails->cbankBic         = isset($response['invoice_bic']) ? $response['invoice_bic'] : $_SESSION[$this->paymentName]['invoice_bic'];
            $insertInvoiceDetails->cRechnungDuedate = isset($response['due_date']) ? $response['due_date'] : $_SESSION[$this->paymentName]['due_date'];
            $insertInvoiceDetails->dDatum           = date('Y-m-d H:i:s');
            $insertInvoiceDetails->cReferenceValues = serialize( $this->getPaymentReferenceValues( $this->paymentName ) );

            Shop::DB()->insertRow('xplugin_novalnetag_tpreinvoice_transaction_details', $insertInvoiceDetails);
        }

        if (!empty($response['subs_id'])) {

            $insertSubscription = new stdClass();
            $insertSubscription->cBestellnummer = $insertOrder->cNnorderid;
            $insertSubscription->nSubsId        = $response['subs_id'];
            $insertSubscription->nTid           = $tid;
            $insertSubscription->dSignupDate    = date('Y-m-d H:i:s');

            Shop::DB()->insertRow('xplugin_novalnetag_tsubscription_details', $insertSubscription);
        }

        if (!empty($_SESSION['nn_aff_id'])) {

            $insertAffiliate = new stdClass();
            $insertAffiliate->nAffId      = $this->vendorid;
            $insertAffiliate->cCustomerId = $order->kKunde;
            $insertAffiliate->nAffOrderNo = $insertOrder->cNnorderid;

            Shop::DB()->insertRow('xplugin_novalnetag_taff_user_detail', $insertAffiliate);
        }
    }

    /**
     * To get a unique string
     *
     * @param none
     * @return string
     */
    public function getRandomString()
    {
        $randomwordarray = explode(',', 'a,b,c,d,e,f,g,h,i,j,k,l,m,1,2,3,4,5,6,7,8,9,0');
        shuffle($randomwordarray);
        return substr(implode($randomwordarray,''), 0, 30);
    }

    /**
     * To set the error
     *
     * @param none
     * @return none
     */
    public function setError()
    {
        global $hinweis;

        $error = isset($_SESSION['novalnet']['error']) ? $_SESSION['novalnet']['error'] : (isset($_SESSION['fraud_check_error']) ? $_SESSION['fraud_check_error'] : '');

        if (!empty($error)) {
            $hinweis = $error;
                unset($_SESSION['novalnet']['error'], $_SESSION['fraud_check_error']);
        }
    }

    /**
     * Used to encode the data
     *
     * @param string/double $data
     * @return string
     */
    private function generateEncode($data)
    {
        $paymentKey = $this->key_password;
        if (!empty($_SESSION['nn_aff_id'])) {
            $affDetails = $this->getAffiliateDetails();
            $paymentKey = $affDetails[2];
        }

        if (!function_exists('base64_encode') or !function_exists('pack') or !function_exists('crc32')) {
          return'Error: func n/a';
        }

        try {
            $crc = sprintf('%u', crc32($data));
            $data = $crc."|".$data;
            $data = bin2hex($data.$paymentKey);
            $data = strrev(base64_encode($data));
        }
        catch (Exception $e){
          echo('Error: '.$e);
        }
        return $data;
    }

    /**
     * To get the encoded array
     *
     * @param array $data
     * @return array
     */
    private function generateEncodeArray(&$data)
    {
        foreach ($data as $key => $val) {
            $data[$key] = $this->generateEncode($val);
        }
        return $data;
    }

    /**
     * To generate the hash value
     *
     * @param array $h
     * @return string
     */
    private function generateHashValue($h)
    {
        $parameterKeys = $this->getBasicParametersNames();

        if (!empty($_SESSION['nn_aff_id'])) {
            $affDetails = $this->getAffiliateDetails();
            $this->key_password = $affDetails[2];
        }
        if (!$h)
          return 'Error: no data';
        if (!function_exists('md5')) {
          return 'Error: func n/a';
        }

        return md5( $h[$parameterKeys['authcode']] . $h[$parameterKeys['product']] . $h[$parameterKeys['tariff']] . $h['amount'] . $h['test_mode'] . $h['uniqid'] . strrev($this->key_password));
    }

    /**
     * Used to decode the data
     *
     * @param string/bool $data
     * @param bool $redirectOnCancel
     * @return string
     */
    public function generateDecode($data, $redirectOnCancel = false)
    {
        $paymentKey = $redirectOnCancel ? trim($GLOBALS['oPlugin']->oPluginEinstellungAssoc_arr['key_password']) : $this->key_password;

        if (!empty($_SESSION['nn_aff_id'])) {
            $affDetails = $this->getAffiliateDetails();
            $paymentKey = $affDetails->cAffAccesskey;
        }

        try {
        $data = base64_decode(strrev($data));
        $data = pack("H".strlen($data), $data);
        $data = substr($data, 0, stripos($data, $paymentKey));
        $pos  = strpos($data, "|");

            if ($pos === false) {
                return("Error: CKSum not found!");
            }
            $crc    = substr($data, 0, $pos);
            $value  = trim(substr($data, $pos+1));
            if ($crc !=  sprintf('%u', crc32($value))) {
                return("Error; CKSum invalid!");
            }
        return $value;
        }
        catch (Exception $e) {
            echo('Error: '.$e);
        }
    }

    /**
     * To check hash from response
     *
     * @param array $request
     * @return bool
     */
    public function checkHash($request)
    {
        $parameterKeys = $this->getBasicParametersNames();

        if (!$request) return false; #'Error: no data';
            $h[$parameterKeys['authcode']]  = $request[$parameterKeys['authcode']];
            $h[$parameterKeys['product']]   = $request[$parameterKeys['product']];
            $h[$parameterKeys['tariff']]    = $request[$parameterKeys['tariff']];
            $h['amount']      = $request['amount'];
            $h['test_mode']   = $request['test_mode'];
            $h['uniqid']      = $request['uniqid'];

        if ($request['hash2'] != $this->generateHashValue($h)) {
            return false;
        }

        return true;
    }

    /**
     * Returns names of the basic request parameters
     *
     * @param none
     * @return array
     */
    public function getBasicParametersNames()
    {
        return ( $this->paymentName == 'novalnet_cc' ? array( 'vendor' => 'vendor_id', 'authcode' => 'vendor_authcode', 'tariff' => 'tariff_id', 'product' => 'product_id') : array( 'vendor' => 'vendor', 'authcode' => 'auth_code', 'tariff' => 'tariff', 'product' => 'product' ) );
    }

    /**
     * Add the successful payment method into the shop
     *
     * @param object $order
     * @param array $parsed
     * @return none
     */
    public function changeOrderStatus($order, $parsed)
    {
        $transactionPaid = false;
        $this->name = $order->cZahlungsartName;

        if ($parsed['tid_status'] == 100 && !in_array($parsed['inputval3'], array('novalnet_invoice', 'novalnet_prepayment'))) {
            $incomingPayment->fBetrag = $order->fGesamtsummeKundenwaehrung;
            $incomingPayment->cISO = $order->Waehrung->cISO;
            $incomingPayment->cHinweis = $parsed['tid'];
            $this->addIncomingPayment($order, $incomingPayment);
            $transactionPaid = true;
        }
        $this->setOrderStatus($order->cBestellNr, ($parsed['tid_status'] == 90 ? $this->paypal_pending_status : $this->set_order_status), $transactionPaid);
    }

    /**
     * Sets the order status
     *
     * @param string $orderNo
     * @param string $status
     * @param bool $transactionPaid
     * @param bool $canceledOrder
     * @return none
     */
    public function setOrderStatus($orderNo, $status, $transactionPaid = false, $canceledOrder = false)
    {
        $updateQuery = 'cStatus= "' . constant($status) . '"';

        if ($transactionPaid) {
            $updateQuery.= ',dBezahltDatum = now()';
        }

        if ($canceledOrder) {
            $updateQuery.= ',cAbgeholt = "Y"';
        }

        Shop::DB()->query('UPDATE tbestellung SET ' . $updateQuery . ' WHERE cBestellNr = "' . $orderNo . '"',4);
    }

    /**
     * Checks & assigns manual limit
     *
     * @param double $amount
     * @return bool
     */
    private function doCheckManualCheckLimit($amount)
    {
        $tidOnhold = 0;

        if ($this->manual_check_limit && NovalnetValidation::isDigits($this->manual_check_limit) && $amount >= $this->manual_check_limit && (in_array($this->paymentName, array('novalnet_sepa', 'novalnet_cc', 'novalnet_invoice')))) {
            $tidOnhold = 1;
        }
        return $tidOnhold;
    }

    /**
     * To get the sepa duration in days
     *
     * @param none
     * @return integer
     */
    private function getSepaDuedate()
    {
        return ( NovalnetValidation::isDigits($this->sepa_due_date) && $this->sepa_due_date > 6 ) ? date('Y-m-d', strtotime('+' .$this->sepa_due_date. 'days')) : date('Y-m-d', strtotime('+7 days'));
    }

    /**
     * Get panhash from database for sepa payment
     *
     * @param none
     * @return string
     */
    public function getSepaRefillHash()
    {
        $panhash = '';
        $hashValue = Shop::DB()->query('SELECT cSepaHash FROM xplugin_novalnetag_tnovalnet_status WHERE cMail = "'.$_SESSION['Kunde']->cMail.'" ORDER BY kSno DESC LIMIT 1', 1);

        $panhash = ( $this->sepa_refill && $hashValue && !empty($hashValue->cSepaHash)) ? $hashValue->cSepaHash : (($this->sepa_autorefill) && !empty($_SESSION[$this->paymentName]['nn_sepapanhash'] ) ? $_SESSION[$this->paymentName]['nn_sepapanhash'] : '');

        return $panhash;
    }

    /**
     * Get duedate for invoice
     *
     * @param none
     * @return integer
     */
    private function getInvoiceDuedate()
    {
        $dueDate =  (!empty($this->invoice_duration) && NovalnetValidation::isDigits($this->invoice_duration)) ? date('Y-m-d',strtotime('+'.$this->invoice_duration.' days' )) : '';
        return $dueDate;
    }

    /**
     * Get gateway timeout limit
     *
     * @param none
     * @return integer
     */
    private function getGatewayTimeout()
    {
        return (!empty($this->gateway_timeout) && NovalnetValidation::isDigits($this->gateway_timeout)) ? $this->gateway_timeout : 240;
    }

    /**
     * Get transaction status
     *
     * @param integer $tidVal
     * @param string $orderNo
     * @param string  $requestType
     * @return array
     */
    public function xmlCall($tidVal, $orderNo, $requestType)
    {
        if (!empty($_SESSION['nn_aff_id'])) {
            list($this->vendorid,$this->authcode) = $this->getAffiliateDetails();
        }

        $urlparam  = '<?xml version="1.0" encoding="UTF-8"?><nnxml><info_request><vendor_id>' . $this->vendorid . '</vendor_id>';
        $urlparam .= '<vendor_authcode>' . $this->authcode . '</vendor_authcode>';
        $urlparam .= '<request_type>'.$requestType.'</request_type>';
        $urlparam .= '<tid>' . $tidVal . '</tid>';
            if($requestType == 'PIN_STATUS') {
                $urlparam .= '<pin>' . trim($_SESSION['post_array']['nn_pin']) . '</pin>';
                $urlparam .= '<lang>' . self::getShopLanguage() . '</lang>';
            }
        $urlparam .='</info_request></nnxml>';
        $data = $this->sendCurlRequest( $urlparam , 'https://payport.novalnet.de/nn_infoport.xml');
        $response = simplexml_load_string($data);
        $response = json_decode(json_encode($response), true);

        return $response;
    }

    /**
     * Make curl server call
     *
     * @param array $data
     * @param string $url
     * @param string $curlProxy
     * @param integer $curlTimeout
     * @return array
     */
    public function sendCurlRequest($data, $url, $curlProxy = '', $curlTimeout = '')
    {
        $timeOut = !empty($curlTimeout) ? $this->getGatewayTimeout() : $curlTimeout;
        $proxy = !empty($curlProxy) ? $this->proxy : $curlProxy;
        $ch = curl_init( $url );
        curl_setopt( $ch, CURLOPT_POST, 1 );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );
        curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 0 );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER,1 );
        curl_setopt( $ch, CURLOPT_TIMEOUT, ($timeOut > 240 ? $timeOut : 240) );
        if (!empty($proxy))
            curl_setopt($ch, CURLOPT_PROXY, $proxy);
        $response = curl_exec( $ch );
        curl_close( $ch );
        return $response;
    }

    /**
     * Get affiliate order details
     *
     * @param none
     * @return array
     */
    public function getAffiliateDetails()
    {
        $affDetails = '';

        if (!isset($_SESSION['nn_aff_id'])) {
            $affCustomer = Shop::DB()->query("SELECT nAffId FROM xplugin_novalnetag_taff_user_detail WHERE cCustomerId=" . $_SESSION['Kunde']->kKunde . " ORDER BY kId DESC LIMIT 1",1);

            $_SESSION['nn_aff_id'] = is_object($affCustomer) ? $affCustomer->nAffId : '';
        }

        if (!empty($_SESSION['nn_aff_id']))
            $affDetails = Shop::DB()->query("SELECT cAffAuthcode, cAffAccesskey FROM xplugin_novalnetag_taffiliate_account_detail WHERE nAffId='" . $_SESSION['nn_aff_id']. "'",1);

        if (!empty($affDetails)) {
            $this->vendorid     = $_SESSION['nn_aff_id'];
            $this->authcode     = $affDetails->cAffAuthcode;
            $this->key_password = $affDetails->cAffAccesskey;
        }

        return array($this->vendorid, $this->authcode, $this->key_password);
    }

    /**
     * Get configuration parameters from plugin/database
     *
     * @param string $orderNo
     * @return array
     */
    public static function getConfigurationDetails($orderNo)
    {
        $oPlugin = self::getPluginObject();
        $nnorder   = Shop::DB()->query("SELECT cKonfigurations FROM xplugin_novalnetag_tnovalnet_status WHERE cNnorderid ='".$orderNo."'", 1);
        $configDb = unserialize($nnorder->cKonfigurations);

        $vendorId = !empty($configDb['vendor']) ? $configDb['vendor'] : trim($oPlugin->oPluginEinstellungAssoc_arr['vendorid']);
        $authcode = !empty($configDb['auth_code']) ? $configDb['auth_code'] : trim($oPlugin->oPluginEinstellungAssoc_arr['authcode']);
        $productId= !empty($configDb['product']) ? $configDb['product'] : trim($oPlugin->oPluginEinstellungAssoc_arr['productid']);
        $tariffId = !empty($configDb['tariff']) ? $configDb['tariff'] : trim($oPlugin->oPluginEinstellungAssoc_arr['tariffid']);
        $proxy    = !empty($configDb['proxy']) ? $configDb['proxy'] : trim($oPlugin->oPluginEinstellungAssoc_arr['proxy']);

        return array($vendorId, $authcode, $productId, $tariffId, $proxy);
    }

    /**
     * To insert the order details into novalnet table for failure
     *
     * @param array $order
     * @param integer $tid
     * @param string $paymentType
     * @param string $comments
     * @param integer $status
     * @param string $customerMail
     * @param array $affiliateDetails
     * @return bool
     */
    public static function insertOrderIntoDBForFailure($order, $tid, $paymentType, $comments, $status, $customerMail, $affiliateDetails = array())
    {
        $lineBreak = "<br/>";

        list ( $vendorId, $authcode, $productId, $tariffId, $proxy ) = self::getConfigurationDetails($order->cBestellNr);

        if (count(array_filter($affiliateDetails)) != 0) {
            $vendorId = $affiliateDetails['vendor'];
            $authcode = $affiliateDetails['authcode'];
        }

        $insertOrder->cNnorderid        = $order->cBestellNr;
        $insertOrder->cKonfigurations   = serialize(array('vendor' => $vendorId, 'auth_code' => $authcode, 'product' => $productId, 'tariff' => $tariffId, 'proxy' => $proxy));
        $insertOrder->nNntid            = $tid;
        $insertOrder->cZahlungsmethode  = $paymentType;
        $insertOrder->cMail             = !empty($customerMail) ? $customerMail : '';
        $insertOrder->nStatuswert       = $status;
        $insertOrder->cKommentare       = $comments . $lineBreak;
        $insertOrder->dDatum            = date('d.m.Y H:i:s') . $lineBreak;
		$insertOrder->nBetrag           = gibPreisString($order->fGesamtsumme) * 100;

        Shop::DB()->insertRow('xplugin_novalnetag_tnovalnet_status', $insertOrder);

        return true;
    }

    /**
     * Get plugin object
     *
     * @param none
     * @return object
     */
    public static function getPluginObject()
    {
        return Plugin::getPluginById('novalnetag');
    }

    /**
     * Returns shop formatted version
     *
     * @param integer $value
     * @return double
     */
    public function getFormattedVersion($value)
    {
        return number_format($value/100,2,'.','');
    }

    /**
     * Get language texts for the fields
     *
     * @param array $languageFields
     * @return array
     */
    public static function getLanguageText($languageFields)
    {
        $PluginObj = self::getPluginObject();

        foreach ($languageFields as $language) {
                $placeholder = str_replace('__NN_','',$language);
                $lang[$placeholder] = $PluginObj->oPluginSprachvariableAssoc_arr[$language];
        }
        return $lang;
    }

    /**
     * Get currency type for the current order
     *
     * @param string $orderNo
     * @return string
     */
    public static function getPaymentCurrency($orderNo)
    {
        $currency = Shop::DB()->query( 'SELECT twaehrung.cISO FROM twaehrung
    LEFT JOIN tbestellung ON twaehrung.kWaehrung = tbestellung.kWaehrung WHERE cBestellNr ="' . $orderNo . '"', 1);

        return $currency->cISO;
    }

    /**
     * Retrieve payment methods stored in the shop
     *
     * @param integer $paymentNo
     * @param integer $pluginId
     * @param boolean $returnPayment
     * @return array
     */
    public static function getPaymentMethod($paymentType, $pluginId, $returnPayment = false)
    {
        $paymentMethods = array('novalnet_invoice' => 'novalnetkaufaufrechnung', 'novalnet_prepayment' => 'novalnetvorauskasse', 'novalnet_paypal' => 'novalnetpaypal', 'novalnet_cc' => 'novalnetkreditkarte', 'novalnet_banktransfer' => utf8_decode('novalnetsofortüberweisung'), 'novalnet_ideal' => 'novalnetideal', 'novalnet_eps' => 'novalneteps', 'novalnet_sepa' => 'novalnetlastschriftsepa', 'novalnet_giropay' => 'novalnetgiropay');
		$paymentArray = array_keys($paymentMethods, $paymentType);
		
        return ((!$returnPayment) ? 'kplugin_' . $pluginId . '_' . $paymentMethods[$paymentType] : $paymentArray[0]);
    }

    /**
     * Get current shop language
     *
     * @param none
     * @return string
     */
    public static function getShopLanguage()
    {
        return $GLOBALS['oSprache']->cISOSprache == 'ger' ? 'DE' : 'EN';
    }

    /**
     * Get payment name from payment settings
     *
     * @param integer $paymentNo
     * @return string
     */
    public static function getPaymentName($paymentNo)
    {
        global $oSprache;

        $paymentMethod = gibZahlungsart(intval($paymentNo));
        $paymentName = Shop::DB()->query("SELECT cName FROM tzahlungsartsprache WHERE kZahlungsart = $paymentMethod->kZahlungsart AND cISOSprache = \"" . $oSprache->cISOSprache . "\"",1);

        return $paymentName->cName;
    }

    /**
     * Get response text from server
     *
     * @param array $response
     * @return string
     */
    public function getResponseText($response)
    {
        global $oPlugin;

        return !empty($response['status_desc']) ? $response['status_desc'] : (!empty($response['status_text']) ? $response['status_text'] : (!empty($response['status_message']) ? $response['status_message'] : html_entity_decode($oPlugin->oPluginSprachvariableAssoc_arr['__NN_transaction_error'])));
    }
}
