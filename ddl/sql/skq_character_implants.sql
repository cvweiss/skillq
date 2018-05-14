
DROP TABLE IF EXISTS `skq_character_implants`;
CREATE TABLE `skq_character_implants` (
  `characterID` int(16) NOT NULL,
  `attributeName` varchar(32) NOT NULL,
  `attributeID` tinyint(2) NOT NULL,
  `baseValue` int(16) NOT NULL,
  `bonus` int(16) NOT NULL DEFAULT '0',
  `implantName` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`characterID`,`attributeName`),
  KEY `characterID` (`characterID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ;

