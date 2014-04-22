DROP TABLE IF EXISTS `Allocations`;
CREATE TABLE `Allocations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `eid` varchar(16) NOT NULL,
  `college` varchar(16) DEFAULT NULL,
  `room` varchar(16) DEFAULT NULL,
  `round` int(2) DEFAULT NULL,
  `apartment` varchar(128) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `eid` (`eid`)
) ENGINE=MyISAM AUTO_INCREMENT=15871 DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `Apartment_Choices`;
CREATE TABLE `Apartment_Choices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `number` varchar(16) NOT NULL,
  `college` varchar(64) NOT NULL,
  `group_id` varchar(16) NOT NULL,
  `choice` int(2) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=14009 DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `College_Choices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `College_Choices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `eid` int(11) NOT NULL,
  `choice_0` varchar(32) NOT NULL,
  `choice_1` varchar(32) NOT NULL,
  `choice_2` varchar(32) NOT NULL,
  `choice_3` varchar(32) NOT NULL,
  `exchange` tinyint(1) NOT NULL,
  `quiet` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2258 DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `Configs`;
CREATE TABLE `Configs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category` varchar(128) NOT NULL DEFAULT 'Global',
  `name` varchar(128) NOT NULL,
  `type` varchar(64) NOT NULL DEFAULT 'String',
  `value` text,
  `structure` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `category_name` (`category`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `Groups`;
CREATE TABLE `Groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `score` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=452 DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `InGroup`;
CREATE TABLE `InGroup` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `group_id` int(11) NOT NULL,
  `eid` varchar(16) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `group_id` (`group_id`,`eid`)
) ENGINE=MyISAM AUTO_INCREMENT=868 DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `People`;
CREATE TABLE `People` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `eid` varchar(16) NOT NULL,
  `account` varchar(32) NOT NULL,
  `fname` varchar(128) NOT NULL,
  `lname` varchar(128) NOT NULL,
  `country` varchar(64) NOT NULL,
  `college` varchar(64) NOT NULL,
  `email` varchar(64) NOT NULL,
  `year` varchar(64) NOT NULL,
  `status` varchar(64) NOT NULL,
  `major` varchar(256) NOT NULL,
  `absent` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Signifies if a user is absent for one semester',
  `isTall` tinyint(1) NOT NULL DEFAULT '0',
  `random_password` varchar(64) DEFAULT NULL,
  `query` varchar(64) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `eid` (`eid`),
  UNIQUE KEY `account` (`account`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM AUTO_INCREMENT=2272 DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `Requests`;
CREATE TABLE `Requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `eid_from` varchar(16) NOT NULL,
  `eid_to` varchar(16) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `eid_from` (`eid_from`,`eid_to`)
) ENGINE=MyISAM AUTO_INCREMENT=347 DEFAULT CHARSET=latin1;
