DROP TABLE xplugin_novalnetag_tnovalnet ;

CREATE TABLE xplugin_novalnetag_tnovalnet_status (
   kSno            	    INT(11) 	 NOT NULL AUTO_INCREMENT COMMENT 'Auto increment ID',
   cNnorderid     	    VARCHAR(64)  NOT NULL COMMENT 'Order ID from shop',
   nNntid               BIGINT(20)   NOT NULL COMMENT 'Transaction ID',
   cKonfigurations	    longtext 	 NOT NULL COMMENT 'Novalnet Merchant configurations',
   cZahlungsmethode     VARCHAR(64)  NOT NULL COMMENT 'Novalnet payment method',
   cMail		        VARCHAR(255) NOT NULL COMMENT 'Customer e-mail address',
   nStatuswert 	 	    INT(11)               COMMENT 'Novalnet status for the order',
   nBetrag              INT(11)      NOT NULL COMMENT 'Order amount in cents',
   cKommentare          longtext     NOT NULL COMMENT 'Order comments',
   dDatum               longtext     NOT NULL COMMENT 'Order date',
   cSepaHash       	    VARCHAR(64)           COMMENT 'Sepa hash for the SEPA payment orders',
   nErstattungsbetrages INT(11)      DEFAULT '0' COMMENT 'Refunded amount in cents',
   PRIMARY KEY (kSno),
   KEY nNntid (nNntid)
);

CREATE TABLE xplugin_novalnetag_tsubscription_details (
  kId  		            INT(11)      NOT NULL     AUTO_INCREMENT COMMENT 'Auto increment ID',
  cBestellnummer        VARCHAR(64)  NOT NULL     COMMENT 'Order ID from shop',
  nSubsId               INT(11)      NOT NULL     COMMENT 'Subscription ID',
  nTid                  BIGINT(20)   NOT NULL     COMMENT 'Novalnet Transaction Reference ID',
  dSignupDate           datetime     NOT NULL     COMMENT 'Subscription signup date',
  cTerminationReason 	VARCHAR(255) DEFAULT NULL COMMENT 'Subscription cancelled reason',
  dTerminationAt 	    datetime     DEFAULT NULL COMMENT 'Subscription terminated date',
  PRIMARY KEY (kId),
  KEY cBestellnummer (cBestellnummer)
);

CREATE TABLE xplugin_novalnetag_tcallback (
  kId				INT(10) 	NOT NULL 	 AUTO_INCREMENT COMMENT 'Auto Increment ID',
  dDatum 			datetime 	NOT NULL     COMMENT 'Callback DATE TIME',
  cZahlungsart 		VARCHAR(64) NOT NULL     COMMENT 'Callback Payment Type',
  nReferenzTid 		BIGINT(20)  NOT NULL     COMMENT 'Callback Reference ID',
  nCallbackTid 		BIGINT(20)  DEFAULT NULL COMMENT 'Original Transaction ID',
  nCallbackAmount 	INT(11)     DEFAULT NULL COMMENT 'Amount in cents',
  cWaehrung 		VARCHAR(64) DEFAULT NULL COMMENT 'Currency',
  cBestellnummer 	VARCHAR(64) NOT NULL	 COMMENT 'Order ID from shop',
  PRIMARY KEY (kId),
  KEY cBestellnummer (cBestellnummer)
);

CREATE TABLE xplugin_novalnetag_tpreinvoice_transaction_details (
  kId 				INT(11) 	 NOT NULL 		AUTO_INCREMENT COMMENT 'Auto Increment ID',
  cBestellnummer 	VARCHAR(64)  DEFAULT NULL 	COMMENT 'Order ID from shop ',
  nTid 				BIGINT(20) 	 NOT NULL 		COMMENT 'Novalnet Transaction Reference ID ',
  nProductId    	INT(11) 	 NOT NULL 		COMMENT 'Product ID',
  bTestmodus 	   	TINYINT(1) 	 DEFAULT '0' 	COMMENT 'Test mode status',
  cKontoinhaber  	VARCHAR(64)  DEFAULT NULL,
  cKontonummer   	VARCHAR(64)  DEFAULT NULL,
  cBankleitzahl  	VARCHAR(64)  DEFAULT NULL,
  cbankName	   		VARCHAR(128) DEFAULT NULL,
  cbankCity 	   	VARCHAR(128) DEFAULT NULL,
  nBetrag 	   		FLOAT 		 NOT NULL 		COMMENT 'Amount in Euro',
  cWaehrung 	   	VARCHAR(64)  NOT NULL 		COMMENT 'Currency',
  cbankIban     	VARCHAR(64)  DEFAULT NULL,
  cbankBic 	   		VARCHAR(64)  DEFAULT NULL,
  cRechnungDuedate 	DATE 		 DEFAULT NULL,
  dDatum 			datetime 	 NOT NULL,
  PRIMARY KEY (kId),
  KEY cBestellnummer (cBestellnummer)
);

CREATE TABLE xplugin_novalnetag_taffiliate_account_detail (
  kId		        INT(11) 		NOT NULL AUTO_INCREMENT COMMENT 'Auto Increment ID',
  nVendorId         INT(11) 		NOT NULL,
  cVendorAuthcode   VARCHAR(64) 	NOT NULL,
  nProductId 	    INT(11) 		NOT NULL,
  cProductUrl       VARCHAR(255)    NOT NULL,
  dActivationDate   datetime 		DEFAULT NULL,
  nAffId 	        INT(11) 		NOT NULL,
  cAffAuthcode      VARCHAR(64) 	NOT NULL,
  cAffAccesskey     VARCHAR(64)  	NOT NULL,
  PRIMARY KEY (kId),
  KEY nAffId (nAffId)
);

CREATE TABLE xplugin_novalnetag_taff_user_detail (
  kId 	 	     INT(11) 	  NOT NULL AUTO_INCREMENT,
  nAffId  	     INT(11) 	  NOT NULL,
  cCustomerId    VARCHAR(64)  NOT NULL,
  nAffOrderNo    VARCHAR(64)  NOT NULL,
  PRIMARY KEY (kId),
  KEY cCustomerId (cCustomerId)
);
