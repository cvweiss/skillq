
DROP TABLE IF EXISTS `skq_emails`;
CREATE TABLE `skq_emails` (
  `emailID` int(11) NOT NULL AUTO_INCREMENT,
  `isSent` tinyint(1) NOT NULL DEFAULT '0',
  `recipient` varchar(64) CHARACTER SET latin1 NOT NULL,
  `subject` varchar(256) CHARACTER SET latin1 NOT NULL,
  `content` mediumtext CHARACTER SET latin1 NOT NULL,
  `insertTime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sentTime` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`emailID`),
  KEY `isSent` (`isSent`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPRESSED;

