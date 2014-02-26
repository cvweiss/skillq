
DROP TABLE IF EXISTS `skq_corporations`;
CREATE TABLE `skq_corporations` (
  `corporationID` int(16) NOT NULL,
  `corporationName` varchar(128) CHARACTER SET utf8 NOT NULL,
  PRIMARY KEY (`corporationID`),
  KEY `corporationName` (`corporationName`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=COMPRESSED;

