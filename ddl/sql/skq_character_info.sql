
DROP TABLE IF EXISTS `skq_character_info`;
CREATE TABLE `skq_character_info` (
  `characterID` int(16) NOT NULL,
  `characterName` varchar(128) CHARACTER SET latin1 NOT NULL,
  `dob` timestamp NULL DEFAULT NULL,
  `corporationID` int(16) DEFAULT NULL,
  `allianceID` int(16) DEFAULT NULL,
  `skillsTrained` int(16) DEFAULT NULL,
  `skillPoints` int(16) DEFAULT NULL,
  `balance` decimal(32,2) DEFAULT NULL,
  `lastChecked` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `queueFinishes` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `unallocated_sp` varchar(16) DEFAULT '0',
  `customOrder` decimal(3,0) DEFAULT '0',
  `grouped` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`characterID`),
  UNIQUE KEY `character_ID` (`characterID`),
  KEY `characterID` (`characterID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPRESSED;

