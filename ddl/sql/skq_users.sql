
DROP TABLE IF EXISTS `skq_users`;
CREATE TABLE `skq_users` (
  `userID` varchar(32) NOT NULL DEFAULT '',
  `characterID` int(32) DEFAULT NULL,
  `dateCreated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `lastAccess` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`userID`),
  UNIQUE KEY `username` (`characterID`),
  KEY `login_index` (`characterID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8  ROW_FORMAT=COMPRESSED;

