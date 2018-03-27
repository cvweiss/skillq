
DROP TABLE IF EXISTS `skq_character_assets`;
CREATE TABLE `skq_character_assets` (
  `characterID` int(16) NOT NULL,
  `itemID` bigint(16) NOT NULL,
  `locationID` int(16) NOT NULL,
  `typeID` int(16) NOT NULL,
  `quantity` bigint(16) NOT NULL,
  `flag` int(2) NOT NULL,
  `singleton` int(2) NOT NULL,
  `rawQuantity` bigint(16) NOT NULL,
  `value` decimal(16,2) NOT NULL DEFAULT '0.00',
  `accounted` smallint(1) NOT NULL DEFAULT '0',
  KEY `characterID` (`characterID`)
) ENGINE=MyIsam DEFAULT CHARSET=latin1 ;

