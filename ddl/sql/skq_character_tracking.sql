
DROP TABLE IF EXISTS `skq_character_tracking`;
CREATE TABLE `skq_character_tracking` (
  `characterID` int(32) DEFAULT NULL,
  `skill_points` int(32) DEFAULT NULL,
  `last_update` datetime NOT NULL,
  UNIQUE KEY `characterID_2` (`characterID`),
  KEY `characterID` (`characterID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

