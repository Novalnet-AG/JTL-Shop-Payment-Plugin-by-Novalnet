<?php

#########################################################
#                                                       #
#  English Language payment 							#
#  method file 	                                        #
#                                                       #
#  Released under the GNU General Public License.       #
#  This free contribution made by request.              #
#  If you have found this script usefull a small        #
#  recommendation as well as a comment on merchant form #
#  would be greatly appreciated.                        #
#                                                       #
#  Script : en_GB.php				                    #
#                                                       #
#########################################################

  #PREPAYMENT#
  define("NOVALNET_PREPAYMENT_WAWI_NAME", "Novalnet Prepayment");
  define("NOVALNET_PREPAYMENT_NAME", "Novalnet Prepayment");
  define("NOVALNET_PREPAYMENT_COMMENT_HEAD", "Please transfer the amount to the following information to our payment service Novalnet AG");
  define("NOVALNET_PREPAYMENT_HOLDER_LABEL", "Account holder : Novalnet AG");
  define("NOVALNET_PREPAYMENT_ACCNO_LABEL", "Account number : ");
  define("NOVALNET_PREPAYMENT_BANKCODE_LABEL", "Bankcode : ");
  define("NOVALNET_PREPAYMENT_BANKNAME_LABEL", "Bank : ");
  define("NOVALNET_PREPAYMENT_AMOUNT_LABEL", "Amount : ");
  define("NOVALNET_PREPAYMENT_REFERENCE_LABEL_1", "Reference 1:");
  define("NOVALNET_PREPAYMENT_REFERENCE_LABEL_2", "Reference 2: TID ");
  define("NOVALNET_PREPAYMENT_REFERENCE_LABEL_3", "Reference 3: Order-ID ");
  define("NOVALNET_PREPAYMENT_NOTE_HEAD", "Only for international transfers:");
  define("NOVALNET_PREPAYMENT_IBAN_LABEL", "IBAN : ");
  define("NOVALNET_PREPAYMENT_SWIFT_LABEL", "BIC : ");

  #INVOICE#
  define("NOVALNET_INVOICE_WAWI_NAME", "Novalnet Invoice");
  define("NOVALNET_INVOICE_NAME", "Novalnet Invoice");
  define("NOVALNET_INVOICE_DUE_DATE", "Due date : ");
  define("NOVALNET_INVOICE_DUE_DATE_ERROR_MSG", "Due date is not valid ");

  #INSTANT BANK#
  define("NOVALNET_INSTANT_WAWI_NAME", "Novalnet Instant Bank Transfer");

  #IDEAL#
  define("NOVALNET_IDEAL_WAWI_NAME", "Novalnet iDEAL");
    
 #DIRECT DEBIT SEPA#
  define("NOVALNET_SEPA_WAWI_NAME", "Novalnet Direct Debit SEPA");
  define("NOVALNET_SEPA_ADDITIONAL_NAME", "Novalnet Direct Debit SEPA");
  define("NOVALNET_SEPA_PAYMENT_DESC", "Your account will be debited upon delivery of goods.");
  define("NOVALNET_SEPASIGNED_PAYMENT_DESC", "Please note that your account will be debited after receiving the signed mandate from you.");
  define("NOVALNET_SEPA_ACCOUNT_ERROR_MSG", "Please confirm IBAN & BIC ");
  define("NOVALNET_SEPA_DUE_DATE_ERROR_MSG", "SEPA Due date is not valid ");
  define("NOVALNET_SEPA_MANDATE_URL", "Download your mandate");
  define("NOVALNET_SEPA_MANDATE_CLICK", "Click here");
  define("NOVALNET_SEPA_ERR_MANDATE_ORDER_NOT_VALID", "Mandate order is invalid ");
  define("NOVALNET_SEPA_ERR_MANDATE_DATE_NOT_VALID", "Mandate signature date is invalid ");
  define("NOVALNET_DDSEPA_ACCOUNT_ERROR_MSG", "* Please enter valid account details!");
  define("NOVALNET_ACCOUNT_INFO_MSG", "Please complete all fields are required");

  #PAYPAL#
  define("NOVALNET_PAYPAL_WAWI_NAME", "Novalnet PayPal");

  #CREDIT CARD 3D SECURE#
  define("NOVALNET_CC3D_WAWI_NAME", "Novalnet Credit Card 3D Secure");
  define("NOVALNET_CC3D_ADDITIONAL_NAME", "Credit Card 3D Secure");
  define("NOVALNET_CC3D_ACCOUNT_NAME", "Credit card holder:*");
  define("NOVALNET_CC3D_ACCOUNT_NUMBER", "Card number:*");
  define("NOVALNET_CC3D_ACCOUNT_DATE", "Expiration Date:*");
  define("NOVALNET_CC3D_ACCOUNT_MONTH", "Month");
  define("NOVALNET_CC3D_ACCOUNT_YEAR", "Year");
  define("NOVALNET_CC3D_ACCOUNT_CVC", "CVC (Verification Code):*");
  define("NOVALNET_CC3D_CVC_DESC", "*On Visa-, Master- and Eurocard you will find the 3 digit CVC-code near the signature field at the rearside of the credit card.<br /><br />The amount will be booked immediately from your credit card when you submit the order.");

  #CREDITCARD#
  define("NOVALNET_CC_WAWI_NAME", "Novalnet Credit Card");
  define("NOVALNET_CC_ADDITIONAL_NAME", "Credit Card");
  define("NOVALNET_CC_PAYMENT_DESC", "The amount will be booked immediately from your credit card when you submit the order.");

  #COMMON CC3D & CC#
  define("NOVALNET_CC3DCC_ACCOUNT_ERROR_MSG", "* Please enter valid credit card details!");
  
  #COMMON#
  define("NOVALNET_TID_LABEL", "Novalnet Transaction ID : ");
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
?>
