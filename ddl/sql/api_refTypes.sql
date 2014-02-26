
DROP TABLE IF EXISTS `api_refTypes`;
CREATE TABLE `api_refTypes` (
  `refTypeID` int(10) NOT NULL,
  `refTypeName` varchar(128) NOT NULL,
  PRIMARY KEY (`refTypeID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

