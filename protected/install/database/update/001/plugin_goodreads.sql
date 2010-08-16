SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE IF NOT EXISTS `goodreads_data` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `source_id` int(10) unsigned NOT NULL,
  `guid` varchar(255) NOT NULL,
  `title` text NOT NULL,
  `content` text,
  `link` varchar(255) NOT NULL,
  `published` varchar(45) NOT NULL,
  PRIMARY KEY  USING BTREE (`id`),
  UNIQUE KEY `DUPLICATES` USING BTREE (`source_id`, `guid`),
  FULLTEXT KEY `SEARCH` (`content`, `title`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;
