DROP TABLE IF EXISTS `gitorious_data`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `gitorious_data` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `source_id` int(10) unsigned NOT NULL,
  `gitorious_id` varchar(255) NOT NULL,
  `title` text NOT NULL,
  `content` text,
  `repository` text,
  `link` varchar(255) NOT NULL,
  `published` varchar(45) NOT NULL,
  PRIMARY KEY  USING BTREE (`id`),
  UNIQUE KEY `DUPLICATES` USING BTREE (`source_id`, `gitorious_id`),
  FULLTEXT KEY `SEARCH` (`content`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

