
DROP TABLE IF EXISTS `skq_implants`;
CREATE TABLE `skq_implants` (
  `typeID` int(16) NOT NULL,
  `typeName` varchar(128) NOT NULL,
  `charisma` tinyint(1) NOT NULL,
  `intelligence` tinyint(1) NOT NULL,
  `memory` tinyint(1) NOT NULL,
  `perception` tinyint(1) NOT NULL,
  `willpower` tinyint(1) NOT NULL,
  PRIMARY KEY (`typeID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1  ROW_FORMAT=COMPRESSED;

