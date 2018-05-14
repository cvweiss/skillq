
DROP TABLE IF EXISTS `skq_character_queue`;
CREATE TABLE `skq_character_queue` (
  `characterID` int(16) NOT NULL,
  `queuePosition` int(3) NOT NULL,
  `typeID` int(16) NOT NULL,
  `level` smallint(1) NOT NULL,
  `startSP` int(16) NOT NULL,
  `endSP` int(16) NOT NULL,
  `startTime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `endTime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`characterID`,`queuePosition`),
  KEY `characterID` (`characterID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1  ROW_FORMAT=COMPRESSED;

