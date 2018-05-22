
DROP TABLE IF EXISTS `skq_character_tracking`;
CREATE TABLE `skq_character_tracking` (
  `characterID` int(32) DEFAULT NULL,
  `skill_points` int(32) DEFAULT NULL,
  `last_update` datetime NOT NULL,
  KEY `characterID` (`characterID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

