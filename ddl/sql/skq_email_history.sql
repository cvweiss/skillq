
DROP TABLE IF EXISTS `skq_email_history`;
CREATE TABLE `skq_email_history` (
  `email` varchar(128) NOT NULL,
  `event` varchar(64) NOT NULL,
  `expireTime` datetime NOT NULL,
  PRIMARY KEY (`email`,`event`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1  ROW_FORMAT=COMPRESSED;

