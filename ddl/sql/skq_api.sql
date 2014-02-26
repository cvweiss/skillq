
DROP TABLE IF EXISTS `skq_api`;
CREATE TABLE `skq_api` (
  `keyRowID` int(11) NOT NULL AUTO_INCREMENT,
  `userID` int(6) NOT NULL,
  `keyID` int(16) NOT NULL,
  `vCode` varchar(128) CHARACTER SET latin1 NOT NULL,
  `errorCode` int(4) NOT NULL DEFAULT '0',
  `lastValidation` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `dateAdded` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `accessMask` int(16) NOT NULL,
  `expires` timestamp NULL DEFAULT NULL,
  `cachedUntil` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`keyRowID`),
  UNIQUE KEY `keyid_vcode` (`keyID`,`vCode`),
  KEY `lastValidation` (`lastValidation`),
  KEY `userID` (`userID`),
  KEY `keyID` (`keyID`),
  KEY `errorCode` (`errorCode`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPRESSED;

