
DROP TABLE IF EXISTS `skq_character_associations`;
CREATE TABLE `skq_character_associations` (
  `char1` int(32) DEFAULT NULL,
  `char2` int(32) DEFAULT NULL,
  UNIQUE KEY `char1_char2` (`char1`,`char2`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ;

