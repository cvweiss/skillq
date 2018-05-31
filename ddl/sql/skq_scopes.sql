
DROP TABLE IF EXISTS `skq_scopes`;
CREATE TABLE `skq_scopes` (
  `characterID` int(32) DEFAULT NULL,
  `scope` varchar(32) DEFAULT NULL,
  `refresh_token` varchar(128) DEFAULT NULL,
  `lastChecked` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `lastErrorCode` decimal(4,0) NOT NULL DEFAULT '0',
  `errorCount` decimal(2,0) NOT NULL DEFAULT '0',
  `lastSsoChecked` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  UNIQUE KEY `char_scope` (`characterID`,`scope`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

