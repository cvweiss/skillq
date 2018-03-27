
DROP TABLE IF EXISTS `skq_users`;
CREATE TABLE `skq_users` (
  `userID` varchar(32) NOT NULL DEFAULT '',
  `username` varchar(128) NOT NULL,
  `dateCreated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `lastAccess` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`userID`),
  UNIQUE KEY `username` (`username`),
  KEY `login_index` (`username`)
) ENGINE=MyIsam DEFAULT CHARSET=utf8  ROW_FORMAT=PAGE;

