
DROP TABLE IF EXISTS `skq_skill_attributes`;
CREATE TABLE `skq_skill_attributes` (
  `typeID` int(16) NOT NULL,
  `timeMultiplier` int(16) DEFAULT NULL,
  `skillLevel` tinyint(1) NOT NULL,
  `primaryAttribute` varchar(16) DEFAULT NULL,
  `secondaryAttribute` varchar(16) DEFAULT NULL,
  `trialTrainable` tinyint(1) DEFAULT NULL,
  `requiredSkill1` int(16) DEFAULT NULL,
  `requiredSkillLevel1` int(16) DEFAULT NULL,
  `requiredSkill2` int(16) DEFAULT NULL,
  `requiredSkillLevel2` int(16) DEFAULT NULL,
  `requiredSkill3` int(16) DEFAULT NULL,
  `requiredSkillLevel3` int(16) DEFAULT NULL,
  PRIMARY KEY (`typeID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ;

