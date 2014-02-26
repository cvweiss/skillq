
DROP TABLE IF EXISTS `skq_email_history`;
CREATE TABLE `skq_email_history` (
  `email` varchar(128) NOT NULL,
  `event` varchar(64) NOT NULL,
  `eventTime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`email`,`event`),
  KEY `eventTime` (`eventTime`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=COMPRESSED;

