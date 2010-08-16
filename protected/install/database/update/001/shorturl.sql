SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE IF NOT EXISTS `shortUrl` (
  `user_id` int(11) NOT NULL,
  `token`   varchar(16) NOT NULL,
  `url`     varchar(256) NOT NULL,
  `internal` tinyint(1) NOT NULL default '1',
  `created` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`token`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;
