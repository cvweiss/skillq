
DROP TABLE IF EXISTS `skq_character_wallet`;
CREATE TABLE `skq_character_wallet` (
  `characterID` int(16) NOT NULL,
  `dttm` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `refID` bigint(16) NOT NULL,
  `refTypeID` int(16) NOT NULL,
  `ownerName1` varchar(128) NOT NULL,
  `ownerID1` int(16) NOT NULL,
  `ownerName2` varchar(128) NOT NULL,
  `ownerID2` int(16) NOT NULL,
  `argName1` int(16) NOT NULL,
  `argID1` int(16) NOT NULL,
  `amount` decimal(32,2) NOT NULL,
  `balance` decimal(32,2) NOT NULL,
  `reason` varchar(512) NOT NULL,
  `taxReceiverID` int(16) NOT NULL,
  `taxAmount` decimal(32,2) NOT NULL,
  UNIQUE KEY `refID` (`refID`),
  KEY `characterID` (`characterID`),
  KEY `dttm` (`dttm`),
  KEY `characterID_2` (`characterID`,`dttm`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1  ROW_FORMAT=COMPRESSED;

