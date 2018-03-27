
DROP TABLE IF EXISTS `skq_character_certs`;
CREATE TABLE `skq_character_certs` (
  `characterID` int(16) NOT NULL,
  `certificateID` int(16) NOT NULL,
  KEY `characterID` (`characterID`)
) ENGINE=MyIsam DEFAULT CHARSET=latin1 ;

