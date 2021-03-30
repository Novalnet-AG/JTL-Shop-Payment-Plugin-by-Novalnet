CREATE TABLE xplugin_novalnetag_tnovalnet (
  `ksno` INT NOT NULL AUTO_INCREMENT,
  `nnorderid` VARCHAR(10) ,
  `nntid` VARCHAR(25) NOT NULL,
  `nnuserno` VARCHAR(30) NOT NULL,
  `zahlung_status` VARCHAR(20) NOT NULL,
  `mandate_present` int(5) NOT NULL DEFAULT '0',
  `zahlung_code` VARCHAR(50) NOT NULL,
  `amount` DECIMAL( 10, 2 ) NOT NULL,
  PRIMARY KEY (`ksno`)
) ENGINE = MYISAM;
