<?php

  #PREPAYMENT#
  define("NOVALNET_PREPAYMENT_WAWI_NAME", "Novalnet Prepayment");
  define("NOVALNET_PREPAYMENT_NAME", "Novalnet Prepayment");
  define("NOVALNET_PREPAYMENT_TID_LABEL", "Novalnet Transaction ID : ");
  define("NOVALNET_PREPAYMENT_COMMENT_HEAD", "Please transfer the amount to the following information to our payment service Novalnet AG");
  define("NOVALNET_PREPAYMENT_HOLDER_LABEL", "Account holder : Novalnet AG");
  define("NOVALNET_PREPAYMENT_ACCNO_LABEL", "Account number : ");
  define("NOVALNET_PREPAYMENT_BANKCODE_LABEL", "Bankcode : ");
  define("NOVALNET_PREPAYMENT_BANKNAME_LABEL", "Bank : ");
  define("NOVALNET_PREPAYMENT_AMOUNT_LABEL", "Amount : ");
  define("NOVALNET_PREPAYMENT_REFRENCE_LABEL", "Reference : TID ");
  define("NOVALNET_PREPAYMENT_NOTE_HEAD", "Only for international transfers:");
  define("NOVALNET_PREPAYMENT_IBAN_LABEL", "IBAN : ");
  define("NOVALNET_PREPAYMENT_SWIFT_LABEL", "SWIFT / BIC : ");

  #INVOICE#
  define("NOVALNET_INVOICE_WAWI_NAME", "Novalnet Invoice");
  define("NOVALNET_INVOICE_NAME", "Novalnet Invoice");
  define("NOVALNET_INVOICE_DUE_DATE", "Due date : ");

  #INSTANT BANK#
  define("NOVALNET_INSTANT_WAWI_NAME", "Novalnet Instant Bank Transfer");

  #IDEAL
  define("NOVALNET_IDEAL_WAWI_NAME", "Novalnet iDEAL");
  
  #SAFETYPAY
  define("NOVALNET_SAFETYPAY_WAWI_NAME", "Novalnet SafetyPay");
  
 #DIRECT DEBIT SEPA
  define("NOVALNET_SEPA_WAWI_NAME", "Novalnet Direct Debit SEPA");
  define("NOVALNET_SEPA_ADDITIONAL_NAME", "Novalnet Direct Debit SEPA");
  define("NOVALNET_SEPA_PAYMENT_DESC", "Your account will be debited upon delivery of goods.");
define("NOVALNET_SEPASIGNED_PAYMENT_DESC", "Please note that your account will be debited after receiving the signed mandate from you.");
  define("NOVALNET_SEPA_ACCOUNT_ERROR_MSG", "Please confirm my IBAN, BIC values");
  define("NOVALNET_SEPA_DUE_DATE_ERROR_MSG", "SEPA Due date is not valid ");
  define("NOVALNET_SEPA_MANDATE_URL", "Download your mandate");
  define("NOVALNET_SEPA_MANDATE_CLICK", "Click here");
  define("NOVALNET_SEPA_ERR_MANDATE_ORDER_NOT_VALID", "Mandate order is invalid ");
  define("NOVALNET_SEPA_ERR_MANDATE_DATE_NOT_VALID", "Mandate signature date is invalid ");

  #PAYPAL
  define("NOVALNET_PAYPAL_WAWI_NAME", "Novalnet PayPal");

  #CREDIT CARD 3D SECURE
  define("NOVALNET_CC3D_WAWI_NAME", "Novalnet Credit Card 3D Secure");
  define("NOVALNET_CC3D_ADDITIONAL_NAME", "Credit Card 3D Secure");
  define("NOVALNET_CC3D_ACCOUNT_NAME", "Credit card holder:*");
  define("NOVALNET_CC3D_ACCOUNT_NUMBER", "Card number:*");
  define("NOVALNET_CC3D_ACCOUNT_DATE", "Expiration Date:*");
  define("NOVALNET_CC3D_ACCOUNT_MONTH", "Month");
  define("NOVALNET_CC3D_ACCOUNT_YEAR", "Year");
  define("NOVALNET_CC3D_ACCOUNT_CVC", "CVC (Verification Code):*");
  define("NOVALNET_CC3D_CVC_DESC", "*On Visa-, Master- and Eurocard you will find the 3 digit CVC-code near the signature field at the rearside of the credit card.<br /><br />The amount will be booked immediately from your credit card when you submit the order.");

  #CREDITCARD
  define("NOVALNET_CC_WAWI_NAME", "Novalnet Credit Card");
  define("NOVALNET_CC_ADDITIONAL_NAME", "Credit Card");
  define("NOVALNET_CC_PAYMENT_DESC", "The amount will be booked immediately from your credit card when you submit the order.");

  #COMMON CC3D & CC
  define("NOVALNET_CC3DCC_ACCOUNT_ERROR_MSG", "* Please enter valid credit card details!");

  #DIRECT DEBIT GERMAN#
  define("NOVALNET_ELVDE_WAWI_NAME", "Novalnet Direct Debit German");
  define("NOVALNET_ELVDE_ADDITIONAL_NAME", "Direct Debit German");
  define("NOVALNET_ELVDE_ACCOUNT_ACDC", "The ACDC-Check Accepted");
  define("NOVALNET_ELVDE_ACDC_ERROR_MSG", "* Please enable ACDC Check.");

  #DIRECT DEBIT GERMAN#
  define("NOVALNET_ELVAT_WAWI_NAME", "Novalnet Direct Debit Austria");
  define("NOVALNET_ELVAT_ADDITIONAL_NAME", "Direct Debit Austria");
  define("NOVALNET_ACCOUNT_INFO_MSG", "Please complete all fields are required");
  define("NOVALNET_ELVATDE_ACCOUNT_HOLDER", "Account holder:*");
  define("NOVALNET_ELVATDE_ACCOUNT_NUMBER", "Account number:*");
  define("NOVALNET_ELVATDE_ACCOUNT_BANKCODE", "Bankcode:*");
  define("NOVALNET_ELVATDE_PAYMENT_DESCRIPTION", "Your account will be debited upon delivery of goods.");
  define("NOVALNET_ELVATDESEPA_ACCOUNT_ERROR_MSG", "* Please enter valid account details!");

  #TELEPHONE
  define("NOVALNET_TELE_WAWI_NAME", "Novalnet Telephone Payment");
  define("NOVALNET_TELE_ADDITIONAL_NAME", "Telephone Payment");
  define("NOVALNET_TELE_PAYMENT_DESC", "Your amount will be added in your telephone bill when you place the order.");
  define("NOVALNET_TELE_PAYMENT_STEPS", "Following steps are required to complete your payment: ");
  define("NOVALNET_TELE_PAYMENT_STEPONE", "Step&nbsp;1: ");
  define("NOVALNET_TELE_PAYMENT_STEPONE_DESC_ONE", "Please call the telephone number displayed: ");
  define("NOVALNET_TELE_PAYMENT_STEPONE_DESC_TWO", "* This call will cost ");
  define("NOVALNET_TELE_PAYMENT_STEPONE_DESC_THREE", "(including VAT) and it is possible only for German landline connection! *");
  define("NOVALNET_TELE_PAYMENT_STEPTWO", "Step&nbsp;2: ");
  define("NOVALNET_TELE_PAYMENT_STEPTWO_DESC", "Please wait for the beep and then hang up the listeners. <br>After your successful call, please proceed with the payment.");
  define("NOVALNET_TELE_AMOUNT_CHANGED_ERROR", "* You have changed the order amount after receiving telephone number, please try again with a new call!");
  define("NOVALNET_TELE_AMOUNT_RANGE_ERROR", "* Amounts below 0,99 Euros and above 10,00 Euros cannot be processed and are not accepted!");
  define("NOVALNET_SECONDCALL_BASIC_ERROR", "* Required parameter not valid!");

  #COMMON#
  define("NOVALNET_BASIC_ERROR_MSG", "* Basic parameter not valid");
  define("NOVALNET_MANUALCHECK_ERROR_MSG", "* Manual limit amount / Product-ID2 / Tariff-ID2 is not valid!");
  define("NOVALNET_MANUALCHECKAMOUNT_ERROR_MSG", "* Manual Check limit field missing/invalid!");
  define("NOVALNET_ORDER_SUCESS_MSG", "Your Order has been Completed Successfully");
  define("NOVALNET_TESTORDER_MSG", "Test Order");
  define("NOVALNET_CHECKHASH_ERROR_MSG", "Check Hash Failed");
  define("NOVALNET_UPDATE_SUCESSORDER_ERRORMSG", "* Unfortunately, this order could not be processed. Please, place a new order!");
  define("NOVALNET_REDIRECTION_MSG", "You will be redirected automatically. If not redirected automatically within 1 minute, click here <br> <input type='submit' name='enter' value='Redirecting...' onClick='this.disabled=\'disabled\';' /></form>");
  define("NOVALNET_TESTMODE_MSG", "<br><br><p style='color:#FF0000;'>Please note: This transaction will run on TEST MODE and the amount will not be charged");
  define("NOVALNET_CUSTOMER_DETAILS_ERROR_MSG", "* Customer name/email fields are not valid");
  define("NOVALNET_CUSTOMER_MAIL_ERROR_MSG", "* Invalid email");
  define("NOVALNET_AMOUNT_ERROR_MSG", "Amount value is not valid");
  define("NOVALNET_KEY_ERROR_MSG", "Payment key value is not valid");
  define("NOVALNET_PAYMENTDURIATION_ERROR_MSG", "* Payment Period Invalid");
?>
