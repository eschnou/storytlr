--
-- Table structure for table `mentions`
--
CREATE TABLE `mentions` (
  `id` int(11) NOT NULL auto_increment,
  `source_id` int(11),
  `item_id` int(11),
  `url` varchar(256) default NULL,
  `entry` text,
  `author_name` varchar(128) default NULL,
  `author_url` varchar(256) default NULL,
  `author_bio` text default NULL,
  `author_avatar` varchar(256),
  `timestamp` datetime NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;