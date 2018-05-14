
DROP TABLE IF EXISTS `skq_item_history`;
CREATE TABLE `skq_item_history` (
  `typeID` int(11) NOT NULL,
  `regionID` int(16) NOT NULL,
  `priceDate` date NOT NULL DEFAULT '0000-00-00',
  `avgPrice` decimal(16,2) NOT NULL,
  `lowPrice` decimal(16,2) NOT NULL,
  `highPrice` decimal(16,2) NOT NULL,
  `volume` bigint(16) NOT NULL,
  PRIMARY KEY (`typeID`,`regionID`,`priceDate`),
  KEY `regionID` (`regionID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1  ROW_FORMAT=DYNAMIC;

