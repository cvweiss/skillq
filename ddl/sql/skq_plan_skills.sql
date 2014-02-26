
DROP TABLE IF EXISTS `skq_plan_skills`;
CREATE TABLE `skq_plan_skills` (
  `rowID` int(16) NOT NULL AUTO_INCREMENT,
  `planID` int(16) NOT NULL,
  `typeID` int(16) NOT NULL,
  `level` tinyint(1) NOT NULL,
  `priority` tinyint(1) NOT NULL,
  PRIMARY KEY (`rowID`),
  KEY `planID` (`planID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

