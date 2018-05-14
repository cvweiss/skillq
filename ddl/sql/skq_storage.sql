
DROP TABLE IF EXISTS `skq_storage`;
CREATE TABLE `skq_storage` (
  `locker` varchar(64) CHARACTER SET latin1 NOT NULL,
  `contents` varchar(256) CHARACTER SET latin1 NOT NULL,
  PRIMARY KEY (`locker`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8  ROW_FORMAT=COMPRESSED;

