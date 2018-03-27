
DROP TABLE IF EXISTS `skq_api_account`;
CREATE TABLE `skq_api_account` (
  `keyRowID` int(16) NOT NULL,
  `paidUntil` timestamp NULL DEFAULT NULL,
  `createDate` timestamp NULL DEFAULT NULL,
  `logonCount` int(16) DEFAULT NULL,
  `logonMinutes` int(16) DEFAULT NULL,
  PRIMARY KEY (`keyRowID`)
) ENGINE=MyIsam DEFAULT CHARSET=latin1  ROW_FORMAT=PAGE;

