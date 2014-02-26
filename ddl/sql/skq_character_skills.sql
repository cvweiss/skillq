
DROP TABLE IF EXISTS `skq_character_skills`;
CREATE TABLE `skq_character_skills` (
  `characterID` int(16) NOT NULL,
  `typeID` int(16) NOT NULL,
  `level` tinyint(1) NOT NULL,
  `skillPoints` int(16) NOT NULL,
  `training` tinyint(1) NOT NULL DEFAULT '0',
  `queue` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`characterID`,`typeID`),
  KEY `characterID` (`characterID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPRESSED;

