
DROP TABLE IF EXISTS `skq_users_config`;
CREATE TABLE `skq_users_config` (
  `characterID` int(3) DEFAULT NULL,
  `key` varchar(64) NOT NULL,
  `value` text NOT NULL,
  UNIQUE KEY `id` (`characterID`,`key`),
  KEY `id_2` (`characterID`),
  KEY `key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8  ROW_FORMAT=COMPRESSED;


