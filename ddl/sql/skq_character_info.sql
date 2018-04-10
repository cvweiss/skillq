
DROP TABLE IF EXISTS `skq_character_info`;
CREATE TABLE `skq_character_info` (
  `characterID` int(16) NOT NULL,
  `characterName` varchar(128) CHARACTER SET latin1 NOT NULL,
  `display` smallint(1) NOT NULL DEFAULT '1',
  `dob` timestamp NULL DEFAULT NULL,
  `corporationID` int(16) DEFAULT NULL,
  `allianceID` int(16) DEFAULT NULL,
  `race` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
  `bloodline` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
  `ancestry` varchar(32) CHARACTER SET latin1 DEFAULT NULL,
  `skillsTrained` int(16) DEFAULT NULL,
  `skillPoints` int(16) DEFAULT NULL,
  `cloneSkillPoints` int(16) DEFAULT NULL,
  `balance` decimal(32,2) DEFAULT NULL,
  `assetsValue` decimal(16,2) NOT NULL DEFAULT '0.00',
  `cachedUntil` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `lastChecked` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `queueFinishes` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `walletCachedUntil` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `subFlag` int(1) NOT NULL DEFAULT '0',
  `unallocated_sp` varchar(16) DEFAULT '0',
  `customOrder` decimal(3,0) DEFAULT '0',
  PRIMARY KEY (`characterID`),
  UNIQUE KEY `character_ID` (`characterID`),
  KEY `characterID` (`characterID`),
  KEY `display` (`display`),
  KEY `cachedUntil` (`cachedUntil`),
  KEY `walletCachedUntil` (`walletCachedUntil`)
) ENGINE=MyIsam DEFAULT CHARSET=utf8  ROW_FORMAT=PAGE;

