
DROP TABLE IF EXISTS `skq_users_config`;
CREATE TABLE `skq_users_config` (
  `id` int(3) NOT NULL,
  `key` varchar(64) NOT NULL,
  `value` text NOT NULL,
  UNIQUE KEY `id` (`id`,`key`),
  KEY `id_2` (`id`),
  KEY `key` (`key`)
) ENGINE=MyIsam DEFAULT CHARSET=utf8  ROW_FORMAT=PAGE;


