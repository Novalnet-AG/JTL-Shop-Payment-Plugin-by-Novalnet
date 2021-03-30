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
 * Script : class.NovalnetInterface.php
 *
 */

require_once('class.Novalnet.php');

class NovalnetInterface extends NovalnetGateway
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
     * Forming payment parameters to paygate
     * @param array $order
     * @return array
     */
    public function buildParametersToPaygate($order)
    {
        $novalnetValidation = new NovalnetValidation();
        $novalnetValidation->basicValidation($this, $order);

        $params['orderNo'] = '';
        if ( $_SESSION['Zahlungsart']->nWaehrendBestellung == 0 )
            $params['orderNo'] = $order->cBestellNr;

        if (in_array($this->paymentName,$this->redirectPayments)) {
            $this->setReturnUrls($params, $order);
        }

        $data = array();
        $this->buildBasicParams($data, $order->fGesamtsumme);
        $this->buildCommonParams($data, $order->Waehrung->cISO, $_SESSION['Kunde']);
        $this->buildAdditionalParams($data, $params);
        $novalnetValidation->validateCustomerParameters($data, $order);
        return $data;
    }

    /**
     * Return during error
     *
     * @param array $parsed
     * @return none
     */
    public function returnOnError($order, $response)
    {
        $oPlugin = NovalnetGateway::getPluginObject();

        if ( $_SESSION['Zahlungsart']->nWaehrendBestellung == 0 || !empty( $_SESSION['nn_during_order'] ) ) {
			
            // Form transaction comments for failure order
            $comments = $this->updateOrderComments($response, $order);
            $comments .= html_entity_decode(!empty($_SESSION['novalnet']['error']) ? $_SESSION['novalnet']['error'] :$this->getResponseText($response));

            $customerEmail = !empty($response['email']) ? $response['email'] : (!empty($_SESSION['Kunde']->cMail) ? $_SESSION['Kunde']->cMail : '');
            $this->insertOrderIntoDBForFailure($order, $response['tid'], $this->paymentName, $comments, (!empty($response['tid_status']) ? $response['tid_status'] : $response['status'] ), $customerEmail);

            $this->addReferenceToComment($order->kBestellung, $comments);
            $this->setOrderStatus($order->cBestellNr, $oPlugin->oPluginEinstellungAssoc_arr['cancel_order_status'],false,true);
            if($order->Zahlungsart->nMailSenden & ZAHLUNGSART_MAIL_STORNO ) {
				$this->sendMail($order->kBestellung, MAILTEMPLATE_BESTELLUNG_STORNO);
			}
            unset($_SESSION['nn_during_order']);
            header('Location:' . $order->BestellstatusURL);
            exit();
       }
       header( 'Location:' . Shop::getURL() . '/bestellvorgang.php?editZahlungsart=1' );
       exit();
    }

    /**
     * Process while handling handle_notification url
     *
     * @param array $order
     * @param array $response
     * @return none
     */
    public function handleViaNotification($order, $response)
    {
        $oPlugin = NovalnetGateway::getPluginObject();
        $paymenthash = $this->generateHash($order);

        if (in_array($response['status'], array(90, 100))) {

            if (isset($_SESSION['nn_during_order'])) {

                if (in_array($this->paymentName, $this->redirectPayments) && !$this->checkHash($response)) {
                    $_SESSION['novalnet']['error'] = html_entity_decode($oPlugin->oPluginSprachvariableAssoc_arr['__NN_hash_error']);
                    $response['status'] = $response['tid_status'] = 0;
                    $this->returnOnError($order, $response); // Redirects to the error page
                }

                $comments = $this->updateOrderComments($response, $order);
                NovalnetGateway::addReferenceToComment($order->kBestellung, $comments);
                $this->sendMail($order->kBestellung, MAILTEMPLATE_BESTELLUNG_AKTUALISIERT);
            } else{
                $this->postBackCall($response, $order->cBestellNr, $order->cKommentar);
            }

            $this->changeOrderStatus($order, $response);
            $this->insertOrderIntoDB($response, $order->kBestellung);
            unset($_SESSION[$this->paymentName]);
            unset($_SESSION['nn_aff_id']);
            unset($_SESSION['nn_during_order']);
            header('Location: ' .  Shop::getURL() . '/bestellabschluss.php?i=' . $paymenthash);
            exit();
        } else{
            $_SESSION['novalnet']['error'] = utf8_decode($this->getResponseText($response));
            $this->returnOnError($order, $response);
        }
    }

    /**
     * Performs redirection for payments
     *
     * @param array $order
     * @return none
     */
    public function doRedirectionCall($order)
    {
        global $oPlugin;
        $_SESSION['Zahlkungsart'] = true;
        $this->novalnetSessionUnset($this->paymentName);

        Shop::Smarty()->assign( array(
            'paymentUrl'  => $this->setPaymentConfiguration(),
            'datas'       => $this->buildParametersToPaygate($order),
            'button_text' => $oPlugin->oPluginSprachvariableAssoc_arr['__NN_redirection_button_text'],
            'browser_text' => $oPlugin->oPluginSprachvariableAssoc_arr['__NN_redirection_browser_text'],
            'paymentMethodURL' => Shop::getURL() . '/' . PFAD_PLUGIN . $oPlugin->cVerzeichnis . '/' . PFAD_PLUGIN_VERSION . $oPlugin->nVersion . '/' . PFAD_PLUGIN_PAYMENTMETHOD,
            'is_iframe'   => $this->paymentName == 'novalnet_cc' ? true : false,
            'is_redirect' => in_array($this->paymentName, $this->redirectPayments)
        ) );

        if ( $this->paymentName == 'novalnet_cc' ) {
            Shop::Smarty()->assign( 'content',  Shop::Smarty()->fetch( str_replace( 'frontend', 'paymentmethod', $oPlugin->cFrontendPfad ) . '/template/novalnet_cc_iframe.tpl' ) );
        }
    }

    /**
     * Make payment first call to server
     *
     * @param array $order
     * @return none
     */
    public function doPaymentCall($order)
    {
        global $oPlugin;

        $_SESSION['Zahlkungsart'] = true;
        $data     = $this->buildParametersToPaygate($order);
        $query    = http_build_query($data);
        $response = $this->sendCurlRequest($query, $this->setPaymentConfiguration());
        parse_str( $response, $parsed );

        if($parsed['status'] == 100) {
             $_SESSION['novalnet']['success'] = $parsed;
             $novalnetValidation = new NovalnetValidation();
             if (isset($this->pin_by_callback) && $this->pin_by_callback != '0' && $novalnetValidation->isValidFraudCheck($this)) {
                $this->fraudModuleComments($parsed);
                $_SESSION['novalnet']['error'] = ($this->pin_by_callback == '2') ? $oPlugin->oPluginSprachvariableAssoc_arr['__NN_sms_pin_message'] : $oPlugin->oPluginSprachvariableAssoc_arr['__NN_callback_pin_message'];
                $_SESSION['novalnet']['fraud_module_active'] = TRUE;
                header('Location:' . Shop::getURL() . '/bestellvorgang.php?editZahlungsart=1');
                exit;
            }
        }else {
            $_SESSION['novalnet']['error'] = utf8_decode($this->getResponseText($parsed));
            $this->returnOnError($order,$parsed);
        }
    }

    /**
     * Make second call to server
     *
     * @param array $order
     * @return none
     */
    public function doSecondCall($order)
    {
        $aryResponse = $this->xmlCall($_SESSION[$this->paymentName]['tid'], $order->cBestellNr,(!empty($_SESSION['post_array']['nn_forgot_pin']) ? 'TRANSMIT_PIN_AGAIN' : 'PIN_STATUS'));

        if ($aryResponse['status'] == 100 ) {
            $_SESSION[$this->paymentName]['tid_status'] = $aryResponse['tid_status'];
            return true;
        } else {
            if ($aryResponse['status'] == '0529006') {
                $_SESSION[$this->paymentName.'_invalid']    = TRUE;
                $_SESSION[$this->paymentName.'_time_limit'] = time()+(30*60);
                unset($_SESSION[$this->paymentName]['tid']);
            }elseif ($aryResponse['status'] == '0529008'){
                unset($_SESSION[$this->paymentName]['tid']);
            }
            $_SESSION['novalnet']['error'] = utf8_decode(!empty($aryResponse['status_message']) ? $aryResponse['status_message'] : (!empty($aryResponse['pin_status']['status_message']) ? $aryResponse['pin_status']['status_message'] : ''));
            $this->returnOnError($order, $aryResponse);
        }
    }

    /**
     * To check whether payment is enabled
     *
     * @param string $paymentType
     * @return bool
     */
    public function isPaymentEnabled($paymentType)
    {
        $oPlugin = NovalnetGateway::getPluginObject();

        $novalnetValidation = new NovalnetValidation();

        $config = array('vendorid', 'productid', 'authcode', 'tariffid', 'key_password');
        foreach ($config as $configuration) {
            $this->$configuration = trim($oPlugin->oPluginEinstellungAssoc_arr[$configuration]);
        }

        if ((isset($_SESSION[$paymentType.'_invalid']) && (time() < $_SESSION[$paymentType.'_time_limit'])) || ($oPlugin->oPluginEinstellungAssoc_arr[$paymentType.'_enablemode'] == 0) || ($novalnetValidation->isConfigInvalid($this))) {
            return true;
        }
    }

    /**
     * To display additional fields for fraud prevention setup
     *
     * @param none
     * @return none
     */
    public function displayFraudCheck()
    {
        $this->novalnetSessionUnset($this->paymentName);

        $novalnetValidation = new NovalnetValidation();

        if ($this->pin_by_callback == '0' || !$novalnetValidation->isValidFraudCheck($this)) {
            return true;
        } else {
            if (isset($_SESSION[$this->paymentName]['tid'])) {
                    Shop::Smarty()->assign('pin_error', true);
            } else {
                if($this->pin_by_callback == '1')
                    Shop::Smarty()->assign('pin_by_callback',true);
                elseif($this->pin_by_callback == '2')
                    Shop::Smarty()->assign('pin_by_sms',true);
            }
        }
    }

    /**
     * Validates the order amount after payment first call
     *
     * @param integer $orderAmount
     * @return none
     */
    public function orderAmountCheck($orderAmount)
    {
        global $oPlugin;
		
        if ($orderAmount != $_SESSION['novalnet']['amount']) {
            $_SESSION['fraud_check_error'] = $oPlugin->oPluginSprachvariableAssoc_arr['__NN_amount_fraudmodule_error'];
            $this->novalnetSessionUnset($this->paymentName);
            unset($_SESSION[$this->paymentName]['tid']);
            unset($_SESSION['novalnet']);
            header('Location:'. Shop::getURL() .'/bestellvorgang.php?editZahlungsart=1&');
            exit;
        }
    }

    /**
     * Storing response parameters for order comments
     *
     * @param array $response
     * @return none
     */
    public function fraudModuleComments($response)
    {
        $_SESSION[$this->paymentName]['test_mode']  = (isset($response['test_mode']) && !empty($response['test_mode'])) ? $response['test_mode'] : '';
        $_SESSION[$this->paymentName]['tid']        = $response['tid'];
        $_SESSION['novalnet']['amount']             = $response['amount'];
        $_SESSION[$this->paymentName]['currency']   = $response['currency'];

        if (in_array($this->paymentName, $this->invoicePayments)) {
            $_SESSION[$this->paymentName]['due_date'] = $response['due_date'];
            $_SESSION[$this->paymentName]['invoice_iban'] = $response['invoice_iban'];
            $_SESSION[$this->paymentName]['invoice_bic'] = $response['invoice_bic'];
            $_SESSION[$this->paymentName]['invoice_bankname'] = $response['invoice_bankname'];
            $_SESSION[$this->paymentName]['invoice_bankplace'] = $response['invoice_bankplace'];
            $_SESSION[$this->paymentName]['invoice_account'] = $response['invoice_account'];
            $_SESSION[$this->paymentName]['invoice_bankcode'] = $response['invoice_bankcode'];
        }
    }
}
