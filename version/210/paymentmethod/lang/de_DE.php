<?php

  #PREPAYMENT#
  define("NOVALNET_PREPAYMENT_WAWI_NAME", "Novalnet Vorauskasse");
  define("NOVALNET_PREPAYMENT_NAME", "Novalnet Vorauskasse");
  define("NOVALNET_PREPAYMENT_TID_LABEL", "Novalnet Transaktions-ID : ");
  define("NOVALNET_PREPAYMENT_COMMENT_HEAD", "Bitte &uuml;berweisen Sie den Betrag mit der folgenden Information an unseren ZahlungsdienstleisterNovalnetAG");
  define("NOVALNET_PREPAYMENT_HOLDER_LABEL", "Kontoinhaber : Novalnet AG");
  define("NOVALNET_PREPAYMENT_ACCNO_LABEL", "Kontonummer : ");
  define("NOVALNET_PREPAYMENT_BANKCODE_LABEL", "Bankleitzahl : ");
  define("NOVALNET_PREPAYMENT_BANKNAME_LABEL", "Bank : ");
  define("NOVALNET_PREPAYMENT_AMOUNT_LABEL", "Betrag : ");
  define("NOVALNET_PREPAYMENT_REFRENCE_LABEL", "Verwendungszweck : TID ");
  define("NOVALNET_PREPAYMENT_NOTE_HEAD", "Nur bei Auslands&uuml;berweisungen:");
  define("NOVALNET_PREPAYMENT_IBAN_LABEL", "IBAN : ");
  define("NOVALNET_PREPAYMENT_SWIFT_LABEL", "SWIFT / BIC : ");

  #INVOICE#
  define("NOVALNET_INVOICE_WAWI_NAME", "Novalnet Kauf auf Rechnung");
  define("NOVALNET_INVOICE_NAME", "Novalnet Kauf auf Rechnung");
  define("NOVALNET_INVOICE_DUE_DATE", "F&auml;lligkeitsdatum : ");

  #INSTANT BANK#
  define("NOVALNET_INSTANT_WAWI_NAME", "Novalnet Sofortüberweisung");

  #IDEAL
  define("NOVALNET_IDEAL_WAWI_NAME", "Novalnet iDEAL");
  
  #SAFETYPAY
  define("NOVALNET_SAFETYPAY_WAWI_NAME", "Novalnet Safetypay");
  
  #DIRECT DEBIT SEPA
  define("NOVALNET_SEPA_WAWI_NAME", "Novalnet Lastschrift SEPA");
  define("NOVALNET_SEPA_ADDITIONAL_NAME", "Novalnet Lastschrift SEPA");
  define("NOVALNET_SEPA_ACCOUNT_ERROR_MSG", "Bitte best&auml;tigen Sie IBAN und BIC");
  define("NOVALNET_SEPA_PAYMENT_DESC", "Die Belastung Ihres Kontos erfolgt mit dem Versand der Ware.");
define("NOVALNET_SEPASIGNED_PAYMENT_DESC", "Bitte nehmen Sie zur Kenntnis, dass Ihr Konto nach dem Eingehen Ihres unterschriebenen Auftrags belasted wird");
  define("NOVALNET_SEPA_DUE_DATE_ERROR_MSG", "SEPA F&auml;lligkeitsdatum Ung&uuml;ltiger ");
  define("NOVALNET_SEPA_MANDATE_URL", "Ihr Mandat als PDF");
  define("NOVALNET_SEPA_MANDATE_CLICK", "Klicken Sie hier");
  define("NOVALNET_SEPA_ERR_MANDATE_ORDER_NOT_VALID", "Mandat Belastung ist ungültige  ");
  define("NOVALNET_SEPA_ERR_MANDATE_DATE_NOT_VALID", "Mandat Signatur Datum ist ungültige  ");

  #PAYPAL
  define("NOVALNET_PAYPAL_WAWI_NAME", "Novalnet PayPal");

  #CREDIT CARD 3D SECURE
  define("NOVALNET_CC3D_WAWI_NAME", "Novalnet Kreditkarte 3D Secure");
  define("NOVALNET_CC3D_ADDITIONAL_NAME", "Kreditkarte 3D Secure");
  define("NOVALNET_CC3D_ACCOUNT_NAME", "Kreditkarteninhaber:*");
  define("NOVALNET_CC3D_ACCOUNT_NUMBER", "Kartennummer:*");
  define("NOVALNET_CC3D_ACCOUNT_DATE", "G&uuml;ltigkeit (Monat/Jahr):*");
  define("NOVALNET_CC3D_ACCOUNT_MONTH", "Monat");
  define("NOVALNET_CC3D_ACCOUNT_YEAR", "Jahr");
  define("NOVALNET_CC3D_ACCOUNT_CVC", "CVC (Pr&uuml;fziffer):*");
  define("NOVALNET_CC3D_CVC_DESC", "* Bei Visa-, Master- und Eurocard besteht der CVC-Code aus den drei letzten Ziffern im Unterschriftenfeld auf der R&uuml;ckseite der Kreditkarte.<br /><br />Die Belastung Ihrer Kreditkarte erfolgt mit dem Abschluss der Bestellung.");

  #CREDIT CARD
  define("NOVALNET_CC_WAWI_NAME", "Novalnet Kreditkarte");
  define("NOVALNET_CC_ADDITIONAL_NAME", "Kreditkarte");
  define("NOVALNET_CC_PAYMENT_DESC", "* Die Belastung Ihrer Kreditkarte erfolgt mit dem Abschluss der Bestellung.");

  #COMMON CC3D & CC
  define("NOVALNET_CC3DCC_ACCOUNT_ERROR_MSG", "* Geben Sie bitte g&uuml;ltige Kreditkartendaten ein!");

  #DIRECT DEBIT GERMAN#
  define("NOVALNET_ELVDE_WAWI_NAME", "Novalnet Lastschrift Deutschland");
  define("NOVALNET_ELVDE_ADDITIONAL_NAME", "Lastschrift Deutschland");
  define("NOVALNET_ELVDE_ACCOUNT_ACDC", "Der ACDC-Check wird akzeptiert");
  define("NOVALNET_ELVDE_ACDC_ERROR_MSG", "* Aktivieren Sie bitte den ACDC-Check.");

   #DIRECT DEBIT GERMAN#
  define("NOVALNET_ELVAT_WAWI_NAME", "Novalnet Lastschrift Österreich");
  define("NOVALNET_ELVAT_ADDITIONAL_NAME", "Lastschrift &Ouml;sterreich");

  #AT & DE COMMON
  define("NOVALNET_ACCOUNT_INFO_MSG", "Bitte f&uuml;llen Sie alle Felder aus");
  define("NOVALNET_ELVATDE_ACCOUNT_HOLDER", "Kontoinhaber:*");
  define("NOVALNET_ELVATDE_ACCOUNT_NUMBER", "Kontonummer:*");
  define("NOVALNET_ELVATDE_ACCOUNT_BANKCODE", "Bankleitzahl:*");
  define("NOVALNET_ELVATDE_ACCOUNT_ACDC", "Ihre Bonit&auml;t wird von uns gepr&uuml;ft");
  define("NOVALNET_ELVATDE_PAYMENT_DESCRIPTION", "Die Belastung Ihres Kontos erfolgt mit dem Versand der Ware.");
  define("NOVALNET_ELVATDESEPA_ACCOUNT_ERROR_MSG", "* Geben Sie bitte g&uuml;ltige Kontodaten ein!");

  #TELEPHONE
  define("NOVALNET_TELE_WAWI_NAME", "Novalnet Telefonpayment");
  define("NOVALNET_TELE_ADDITIONAL_NAME", "Telefonpayment");
  define("NOVALNET_TELE_PAYMENT_DESC", "Ihr Betrag wird zu Ihrer Telefonrechnung hinzugefügt werden, wenn Sie die Bestellung aufgeben.");
  define("NOVALNET_TELE_PAYMENT_STEPS", "Folgende Schritte sind notwendig, um Ihre Zahlung abzuschlie&szlig;en: ");
  define("NOVALNET_TELE_PAYMENT_STEPONE", "Schritt&nbsp;1: ");
  define("NOVALNET_TELE_PAYMENT_STEPONE_DESC_ONE", "Bitte rufen Sie die angezeigte Telefonnummer an: ");
  define("NOVALNET_TELE_PAYMENT_STEPONE_DESC_TWO", " * Dieser Anruf kostet ");
  define("NOVALNET_TELE_PAYMENT_STEPONE_DESC_THREE", " (inkl. MwSt.) und ist nur f&uuml;r Festnetzanschl&uuml;sse in Deutschland m&ouml;glich! *");
  define("NOVALNET_TELE_PAYMENT_STEPTWO", "Schritt&nbsp;2: ");
  define("NOVALNET_TELE_PAYMENT_STEPTWO_DESC", "Bitte warten Sie auf den Signalton und legen Sie dann den H&ouml;rer auf. <br>War Ihr Anruf erfolgreich, schlie&szlig;en Sie bitte die Zahlung ab.");
  define("NOVALNET_TELE_AMOUNT_CHANGED_ERROR", "* Sie haben die Bestellmenge nach Erhalt der Telefonnummer ge&auml;ndert, versuchen Sie es bitte noch einmal mit einem neuen Anruf!");
  define("NOVALNET_TELE_AMOUNT_RANGE_ERROR", "* Betraege unter 0,99 Euro und ueber 10,00 Euro koennen nicht verarbeitet werden bzw. werden nicht akzeptiert!");
  define("NOVALNET_SECONDCALL_BASIC_ERROR", "*  Ung&uuml;ltiger Wert f&uuml;r Pflichtfeld!");


  #COMMON#
  define("NOVALNET_BASIC_ERROR_MSG", "* Ung&uuml;ltige Parameter f&uuml;r die H&auml;ndlereinstellungen");
  define("NOVALNET_MANUALCHECK_ERROR_MSG", "* Ung&uuml;ltige Bestellgrenze / 2. Produkt-ID / 2. Tarif-ID");
  define("NOVALNET_MANUALCHECKAMOUNT_ERROR_MSG", "* Manueller &Uuml;berpr&uuml;fung feld fehlen/Ung&uuml;ltige!");
  define("NOVALNET_ORDER_SUCESS_MSG", "Ihre Bestellung war erfolgreich!");
  define("NOVALNET_TESTORDER_MSG", "Testbestellung");
  define("NOVALNET_CHECKHASH_ERROR_MSG", "&Uuml;berpr&uumlfen Hash fehlgeschlagen");
  define("NOVALNET_UPDATE_SUCESSORDER_ERRORMSG", "* Leider konnte diese Bestellung nicht verarbeitet werden. Bitte geben Sie eine neue Bestellung auf!");
  define("NOVALNET_REDIRECTION_MSG", "Sie werden in K&uuml;rze automatisch weitergeleitet... Sollte die Weiterleitung fehlschlagen oder l&auml;nger als 1 Minute dauern, klicken Sie bitte hier<br><input type='submit' name='enter' value='Weiterleitung...' onClick='this.disabled=\'disabled\';' /></form>");
  define("NOVALNET_TESTMODE_MSG", "<br><br><p style='color:#FF0000;'>Bitte beachten Sie: Diese Transaktion wird im Test-Modus ausgef&uuml;hrt werden und der Betrag wird nicht belastet werden.");
  define("NOVALNET_CUSTOMER_DETAILS_ERROR_MSG", "* Ung&uuml;ltige Werte f&uuml;r die Felder Kundenname-/email.");
  define("NOVALNET_CUSTOMER_MAIL_ERROR_MSG", "* Ung&uuml;ltige E-Mail");
  define("NOVALNET_AMOUNT_ERROR_MSG", "* Ung&uuml;ltiger Betrag");
  define("NOVALNET_KEY_ERROR_MSG", "* Ung&uuml;ltiger Wert f&uuml;r das Feld Zahlungsschl&uuml;ssel");
  define("NOVALNET_PAYMENTDURIATION_ERROR_MSG", "* Zahlungszeitraum Ung&uuml;ltige");

?>
