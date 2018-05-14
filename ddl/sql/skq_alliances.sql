
DROP TABLE IF EXISTS `skq_alliances`;
CREATE TABLE `skq_alliances` (
  `allianceID` int(16) NOT NULL,
  `allianceName` varchar(128) CHARACTER SET utf8 NOT NULL,
  `lastUpdate` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`allianceID`),
  KEY `allianceName` (`allianceName`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1  ROW_FORMAT=COMPRESSED;

