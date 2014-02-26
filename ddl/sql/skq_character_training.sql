
DROP TABLE IF EXISTS `skq_character_training`;
CREATE TABLE `skq_character_training` (
  `characterID` int(16) NOT NULL,
  `trainingStartTime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `trainingEndTime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `trainingTypeID` int(16) NOT NULL,
  `trainingStartSP` int(16) NOT NULL,
  `trainingDestinationSP` int(16) NOT NULL,
  `trainingToLevel` tinyint(1) NOT NULL,
  PRIMARY KEY (`characterID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPRESSED;

