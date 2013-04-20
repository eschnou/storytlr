--
-- Table structure for table soundcloud
--
CREATE TABLE IF NOT EXISTS `soundcloud_data` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `source_id` int(10) unsigned NOT NULL,
  `track_id` varchar(255) NOT NULL,
  `title` text NOT NULL,
  `description` text,
  `artwork_url` varchar(255) NOT NULL,
  `permalink_url` varchar(255) NOT NULL,
  `stream_url` varchar(255) NOT NULL,
  `uri` varchar(255) NOT NULL,
  PRIMARY KEY  USING BTREE (`id`),
  UNIQUE KEY `DUPLICATES` USING BTREE (`source_id`, `track_id`),
  FULLTEXT KEY `SEARCH` (`description`, `title`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

