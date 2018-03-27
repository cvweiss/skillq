
DROP TABLE IF EXISTS `skq_account_associations`;
CREATE TABLE `skq_account_associations` (
  `userID` varchar(32) DEFAULT NULL,
  `characterID` int(32) DEFAULT NULL,
  UNIQUE KEY `userID` (`userID`,`characterID`)
) ENGINE=MyIsam DEFAULT CHARSET=latin1 ;

