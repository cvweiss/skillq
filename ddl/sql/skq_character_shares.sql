
DROP TABLE IF EXISTS `skq_character_shares`;
CREATE TABLE `skq_character_shares` (
  `userID` int(16) NOT NULL,
  `shareID` varchar(64) NOT NULL,
  `characterID` int(16) NOT NULL,
  `expirationTime` timestamp NULL DEFAULT NULL,
  `views` int(8) NOT NULL DEFAULT '0',
  PRIMARY KEY (`shareID`),
  KEY `characterID` (`characterID`),
  KEY `expirationTime` (`expirationTime`),
  KEY `userID` (`userID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=COMPRESSED;

