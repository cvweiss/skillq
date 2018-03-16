
DROP TABLE IF EXISTS `ccp_dgmAttributeCategories`;
CREATE TABLE `ccp_dgmAttributeCategories` (
  `categoryID` tinyint(3) unsigned NOT NULL,
  `categoryName` varchar(100) DEFAULT NULL,
  `categoryDescription` varchar(400) DEFAULT NULL,
  PRIMARY KEY (`categoryID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


LOCK TABLES `ccp_dgmAttributeCategories` WRITE;
INSERT INTO `ccp_dgmAttributeCategories` VALUES (1,'Fitting','Fitting capabilities of a ship'),(2,'Shield','Shield attributes of ships'),(3,'Armor','Armor attributes of ships'),(4,'Structure','Structure attributes of ships'),(5,'Capacitor','Capacitor attributes for ships'),(6,'Targeting','Targeting Attributes for ships'),(7,'Miscellaneous','Misc. attributes'),(8,'Required Skills','Skill requirements'),(9,'NULL','Attributes already checked and not going into a category'),(10,'Drones','All you need to know about drones'),(12,'AI','Attribs for the AI configuration'),(17,'Speed','Attributes used for velocity, speed and such'),(19,'Loot','Attributes that affect loot drops'),(20,'Remote Assistance','Remote shield transfers, armor, structure and such  '),(21,'EW - Target Painting','NPC Target Painting Attributes'),(22,'EW - Energy Neutralizing','NPC Energy Neutralizing Attributes'),(23,'EW - Remote Electronic Counter Measures','NPC Remote Electronic Counter Measures Attributes'),(24,'EW - Sensor Dampening','NPC Sensor Dampening Attributes'),(25,'EW - Target Jamming','NPC Target Jamming Attributes'),(26,'EW - Tracking Disruption','NPC Tracking Disruption Attributes'),(27,'EW - Warp Scrambling','NPC Warp Scrambling Attributes'),(28,'EW - Webbing','NPC Stasis Webbing  Attributes'),(29,'Turrets','NPC Turrets Attributes'),(30,'Missile','NPC Missile Attributes'),(31,'Graphics','NPC Graphic Attributes'),(32,'Entity Rewards','NPC Entity Rewards Attributes'),(33,'Entity Extra Attributes','NPC Extra Attributes'),(34,'Fighter Abilities','Fighter abilities are like built-in modules on fighters'),(36,'EW - Resistance','Resistances to different types of EWar Effects'),(37,'Bonuses','Bonuses'),(38,'Fighter Attributes','Attributes related to fighters (but not abilities)'),(39,'Superweapons','Attributes relating to Doomsdays and Superweapons'),(40,'Hangars & Bays','Hangars & Bays'),(41,'On Death','Attributes relating to the death of a ship'),(42,'Behavior Attributes','NPC Behavior Attributes'),(43,NULL,NULL);
UNLOCK TABLES;

