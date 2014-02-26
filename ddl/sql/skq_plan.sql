
DROP TABLE IF EXISTS `skq_plan`;
CREATE TABLE `skq_plan` (
  `planID` int(8) NOT NULL AUTO_INCREMENT,
  `userID` int(16) NOT NULL,
  `name` varchar(128) NOT NULL,
  PRIMARY KEY (`planID`),
  KEY `userID` (`userID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

