--
-- Table structure for table `mentions`
--
CREATE TABLE `mentions` (
  `id` int(11) NOT NULL auto_increment,
  `source_id` int(11),
  `item_id` int(11),
  `user_id` int(11) NOT NULL default '0',  
  `url` varchar(256) default NULL,
  `entry` text,
  `author_name` varchar(128) default NULL,
  `author_url` varchar(256) default NULL,
  `author_bio` text default NULL,
  `author_avatar` varchar(256),
  `timestamp` datetime NOT NULL,
  `type` varchar(128) default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `item` USING BTREE (`source_id`, `item_id`, `url`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

