-- Initial database structure for Storytlr.
--
-- This file only contains the database schema (to be used with the install scripts)
-- and no default data (was the case in prior versions).
--
-- Version: 1

--
-- Table structure for table `comments`
--
CREATE TABLE `comments` (
  `id` int(11) NOT NULL auto_increment,
  `source_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL default '0',
  `comment` text,
  `name` varchar(128) default NULL,
  `email` varchar(128) default NULL,
  `website` varchar(256) default NULL,
  `notify` tinyint(1) NOT NULL default '0',
  `timestamp` datetime NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `data`
--
CREATE TABLE `data` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `source_id` int(10) unsigned NOT NULL,
  `service` varchar(45) NOT NULL,
  `type` varchar(45) default NULL,
  `timestamp` datetime NOT NULL,
  `is_hidden` tinyint(1) NOT NULL default '0',
  `user_id` int(10) unsigned NOT NULL,
  `comment_count` int(11) NOT NULL default '0',
  `tag_count` int(11) NOT NULL default '0',
  `slug` varchar(128) default NULL,
  `latitude` float default NULL,
  `longitude` float default NULL,
  `elevation` int(10) unsigned default NULL,
  `accuracy` int(10) unsigned default NULL,
  `has_location` tinyint(1) default '0',
  PRIMARY KEY  USING BTREE (`id`,`source_id`),
  KEY `USER` (`user_id`,`source_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `delicious_data`
--
CREATE TABLE `delicious_data` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `source_id` int(10) unsigned NOT NULL,
  `link` varchar(256) NOT NULL,
  `title` text NOT NULL,
  `subject` text NOT NULL,
  `description` text,
  `dateposted` varchar(45) NOT NULL,
  PRIMARY KEY  USING BTREE (`id`),
  UNIQUE KEY `DUPLICATES` USING BTREE (`source_id`,`link`),
  FULLTEXT KEY `SEARCH` (`title`,`subject`,`description`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `digg_data`
--
CREATE TABLE `digg_data` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `source_id` int(10) unsigned NOT NULL,
  `digg_id` int(10) unsigned NOT NULL,
  `submit_date` varchar(45) NOT NULL,
  `diggs` int(10) unsigned NOT NULL,
  `comments` int(10) unsigned NOT NULL,
  `title` text NOT NULL,
  `description` text NOT NULL,
  `status` varchar(45) NOT NULL,
  `media` varchar(45) NOT NULL,
  `topic` varchar(45) NOT NULL,
  `container` varchar(45) NOT NULL,
  `href` varchar(256) NOT NULL,
  `link` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `DUPLICATES` USING BTREE (`source_id`,`digg_id`),
  FULLTEXT KEY `SEARCH` (`title`,`description`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `files`
--
CREATE TABLE `files` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `user_id` int(10) unsigned NOT NULL,
  `key` varchar(128) NOT NULL,
  `name` varchar(128) NOT NULL,
  `description` varchar(256) NOT NULL,
  `type` varchar(64) NOT NULL,
  `ext` varchar(16) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `flickr_data`
--
CREATE TABLE `flickr_data` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `source_id` int(10) unsigned NOT NULL,
  `photo_id` varchar(45) NOT NULL,
  `secret` varchar(45) NOT NULL,
  `server` varchar(45) NOT NULL,
  `dateupload` varchar(45) NOT NULL,
  `datetaken` varchar(45) NOT NULL,
  `title` varchar(256) NOT NULL,
  `note` text,
  `owner` varchar(45) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `DUPLICATES` USING BTREE (`source_id`,`photo_id`),
  FULLTEXT KEY `SEARCH` (`title`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `googlereader_data`
--
CREATE TABLE `googlereader_data` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `source_id` int(10) unsigned NOT NULL,
  `title` varchar(256) NOT NULL,
  `link` varchar(256) NOT NULL,
  `content` text NOT NULL,
  `published` varchar(45) NOT NULL,
  `note` text NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `DUPLICATES` (`source_id`,`link`),
  FULLTEXT KEY `SEARCH` (`title`,`note`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `laconica_data`
--
CREATE TABLE `laconica_data` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `source_id` int(10) unsigned NOT NULL,
  `status_id` varchar(45) NOT NULL,
  `text` varchar(256) NOT NULL,
  `note` text,
  `source` varchar(256) NOT NULL,
  `truncated` tinyint(3) unsigned NOT NULL,
  `in_reply_to_status` varchar(45) NOT NULL,
  `in_reply_to_user_id` varchar(45) NOT NULL,
  `photo_service` varchar(32) default NULL,
  `photo_key` varchar(32) default NULL,
  `created_at` varchar(45) NOT NULL,
  PRIMARY KEY  USING BTREE (`id`),
  UNIQUE KEY `DUPLIACTES` USING BTREE (`source_id`,`status_id`),
  FULLTEXT KEY `SEARCH` (`text`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `lastfm_data`
--
CREATE TABLE `lastfm_data` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `source_id` int(10) unsigned NOT NULL,
  `artist` varchar(128) NOT NULL,
  `name` varchar(128) NOT NULL,
  `url` varchar(256) NOT NULL,
  `date` varchar(45) NOT NULL,
  `title` text,
  `note` text,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `DUPLICATES` (`source_id`,`artist`,`name`),
  FULLTEXT KEY `SEARCH` (`artist`,`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `pages`
--
CREATE TABLE `pages` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `user_id` int(10) unsigned NOT NULL,
  `prefix` varchar(45) NOT NULL,
  `title` varchar(128) NOT NULL,
  `position` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

--
-- Table structure for table `pages_properties`
--
CREATE TABLE `pages_properties` (
  `page_id` int(10) unsigned NOT NULL,
  `key` varchar(45) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY  (`page_id`,`key`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `picasa_data`
--
CREATE TABLE `picasa_data` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `source_id` int(10) unsigned NOT NULL,
  `photo_id` varchar(45) NOT NULL,
  `published` varchar(45) NOT NULL,
  `link` varchar(256) NOT NULL,
  `title` varchar(256) NOT NULL,
  `description` text NOT NULL,
  `url` varchar(256) NOT NULL,
  `taken_at` varchar(45) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `DUPLICATES` (`source_id`,`photo_id`),
  FULLTEXT KEY `SEARCH` (`title`,`description`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `properties`
--
CREATE TABLE `properties` (
  `user_id` int(10) unsigned NOT NULL default '0',
  `key` varchar(45) NOT NULL,
  `value` text,
  PRIMARY KEY  USING BTREE (`user_id`,`key`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `qik_data`
--
CREATE TABLE `qik_data` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `source_id` int(10) unsigned NOT NULL,
  `title` varchar(256) NOT NULL,
  `description` text NOT NULL,
  `pubDate` varchar(45) NOT NULL,
  `link` varchar(256) NOT NULL,
  `note` text,
  `urlflv` varchar(256) NOT NULL,
  `url3gp` varchar(256) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `DUPLICATES` (`source_id`,`urlflv`),
  FULLTEXT KEY `SEARCH` (`title`,`description`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `rss_data`
--
CREATE TABLE `rss_data` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `source_id` int(10) unsigned NOT NULL,
  `link` varchar(256) NOT NULL,
  `title` varchar(256) NOT NULL,
  `content` text NOT NULL,
  `published` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `DUPLICATES` USING BTREE (`source_id`,`link`),
  FULLTEXT KEY `SEARCH` (`title`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `seesmic_data`
--
CREATE TABLE `seesmic_data` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `source_id` int(10) unsigned NOT NULL,
  `title` varchar(256) NOT NULL,
  `url_player` varchar(256) NOT NULL,
  `created_at` varchar(45) NOT NULL,
  `url_flv` varchar(256) NOT NULL,
  `video_id` varchar(45) NOT NULL,
  `platform_id` varchar(45) NOT NULL,
  `to_username` varchar(45) NOT NULL,
  `note` text,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `DUPLICATES` USING BTREE (`source_id`,`video_id`),
  FULLTEXT KEY `SEARCH` (`title`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `sources`
--
CREATE TABLE `sources` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `user_id` int(10) unsigned NOT NULL,
  `service` varchar(128) NOT NULL,
  `enabled` tinyint(1) NOT NULL default '1',
  `public` tinyint(1) NOT NULL default '1',
  `imported` tinyint(1) NOT NULL default '0',
  `last_update` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  USING BTREE (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

--
-- Table structure for table `sources_properties`
--
CREATE TABLE `sources_properties` (
  `source_id` int(10) unsigned NOT NULL auto_increment,
  `key` varchar(128) NOT NULL,
  `value` varchar(1024) NOT NULL,
  PRIMARY KEY  USING BTREE (`source_id`,`key`)
) ENGINE=MyISAM AUTO_INCREMENT=37426 DEFAULT CHARSET=utf8;

--
-- Table structure for table `stories`
--
CREATE TABLE `stories` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `user_id` int(10) unsigned NOT NULL,
  `title` varchar(256) NOT NULL,
  `subtitle` varchar(256) default NULL,
  `date_from` datetime NOT NULL,
  `date_to` datetime NOT NULL,
  `sources` varchar(128) NOT NULL,
  `is_hidden` tinyint(1) NOT NULL default '1',
  `thumbnail` varchar(128) default NULL,
  `timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `timezone` varchar(64) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `story_items`
--
CREATE TABLE `story_items` (
  `story_id` int(10) unsigned NOT NULL,
  `source_id` int(10) unsigned NOT NULL,
  `item_id` int(10) unsigned NOT NULL,
  `service` varchar(45) NOT NULL,
  `timestamp` datetime NOT NULL,
  `is_hidden` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`story_id`,`source_id`,`item_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `stuffpress_data`
--
CREATE TABLE `stuffpress_data` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `source_id` int(10) unsigned NOT NULL,
  `link` varchar(256) default NULL,
  `title` text,
  `text` text,
  `published` int(10) unsigned NOT NULL,
  `type` varchar(64) NOT NULL,
  `file` varchar(128) NOT NULL,
  `embed` text,
  `url` varchar(256) default NULL,
  PRIMARY KEY  (`id`),
  FULLTEXT KEY `SEARCH` (`title`,`text`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `stumble_data`
--
CREATE TABLE `stumble_data` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `source_id` int(10) unsigned NOT NULL,
  `link` varchar(256) NOT NULL,
  `title` varchar(256) NOT NULL,
  `type` varchar(128) NOT NULL,
  `note` text,
  `published` varchar(45) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `DUPLICATES` USING BTREE (`source_id`,`title`),
  FULLTEXT KEY `SEARCH` (`title`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `tags`
--
CREATE TABLE `tags` (
  `user_id` int(11) NOT NULL default '0',
  `source_id` int(11) NOT NULL default '0',
  `item_id` int(11) NOT NULL default '0',
  `tag` varchar(64) default NULL,
  `symbol` varchar(64) NOT NULL default '',
  PRIMARY KEY  (`item_id`,`source_id`,`user_id`,`symbol`),
  KEY `USER` (`user_id`,`tag`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `tumblr_data`
--
CREATE TABLE `tumblr_data` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `source_id` int(10) unsigned NOT NULL,
  `tumblr_id` varchar(45) NOT NULL,
  `type` varchar(45) NOT NULL,
  `url` varchar(256) default NULL,
  `date` varchar(45) NOT NULL,
  `quote_text` text,
  `quote_source` varchar(256) default NULL,
  `photo_caption` text,
  `photo_url` varchar(256) default NULL,
  `photo_note` text,
  `link_text` varchar(256) default NULL,
  `link_url` varchar(256) default NULL,
  `link_description` text,
  `conversation_title` varchar(256) default NULL,
  `conversation_text` text,
  `regular_title` varchar(256) default NULL,
  `regular_body` text,
  `video_caption` varchar(256) default NULL,
  `video_source` varchar(256) default NULL,
  `video_player` text,
  `video_note` text,
  `audio_caption` varchar(256) default NULL,
  `audio_player` text,
  `audio_note` text,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `DUPLICATES` USING BTREE (`source_id`,`tumblr_id`),
  FULLTEXT KEY `SEARCH` (`quote_text`,`photo_caption`,`link_text`,`link_description`,`regular_title`,`regular_body`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `twitter_data`
--
CREATE TABLE `twitter_data` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `source_id` int(10) unsigned NOT NULL,
  `twitter_id` varchar(45) NOT NULL,
  `text` varchar(256) NOT NULL,
  `note` text,
  `photo_service` varchar(32) default NULL,
  `photo_key` varchar(32) default NULL,
  `source` varchar(256) NOT NULL,
  `truncated` tinyint(3) unsigned NOT NULL,
  `in_reply_to_status` varchar(45) NOT NULL,
  `in_reply_to_user_id` varchar(45) NOT NULL,
  `created_at` varchar(45) NOT NULL,
  PRIMARY KEY  USING BTREE (`id`),
  UNIQUE KEY `DUPLIACTES` USING BTREE (`source_id`,`twitter_id`),
  FULLTEXT KEY `SEARCH` (`text`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `users`
--
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `username` varchar(45) NOT NULL,
  `password` varchar(128) NOT NULL,
  `email` varchar(256) NOT NULL,
  `verified` tinyint(1) NOT NULL default '0',
  `token` varchar(64) NOT NULL,
  `domain` varchar(128) NOT NULL,
  `is_suspended` tinyint(1) default '0',
  `last_login` timestamp NOT NULL default '0000-00-00 00:00:00',
  `last_seen` timestamp NOT NULL default '0000-00-00 00:00:00',
  `hits` int(11) NOT NULL default '0',
  `created` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `email_secret` varchar(45) default NULL,
  `is_beta` tinyint(1) unsigned NOT NULL default '0',
  `update_start` timestamp NOT NULL default '0000-00-00 00:00:00',
  `update_end` timestamp NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  USING BTREE (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

--
-- Table structure for table `vimeo_data`
--
CREATE TABLE `vimeo_data` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `source_id` int(10) unsigned NOT NULL,
  `type` varchar(32) NOT NULL,
  `title` varchar(256) NOT NULL,
  `caption` text NOT NULL,
  `published` varchar(45) NOT NULL,
  `clip_id` varchar(32) NOT NULL,
  `url` varchar(256) NOT NULL,
  `user_name` varchar(128) NOT NULL,
  `user_url` varchar(256) NOT NULL,
  `tags` varchar(256) NOT NULL,
  `duration` int(10) unsigned NOT NULL,
  `width` int(10) unsigned NOT NULL,
  `height` int(10) unsigned NOT NULL,
  `thumbnail` varchar(256) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `DUPLICATES` (`source_id`,`clip_id`),
  FULLTEXT KEY `SEARCH` (`title`,`caption`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `widgets`
--
CREATE TABLE `widgets` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `user_id` int(10) unsigned NOT NULL,
  `prefix` varchar(45) NOT NULL,
  `position` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;

--
-- Table structure for table `widgets_properties`
--
CREATE TABLE `widgets_properties` (
  `widget_id` int(10) unsigned NOT NULL,
  `key` varchar(45) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY  USING BTREE (`widget_id`,`key`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `youtube_data`
--
CREATE TABLE `youtube_data` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `source_id` int(10) unsigned NOT NULL,
  `uri` varchar(256) default NULL,
  `type` varchar(45) NOT NULL,
  `title` varchar(256) NOT NULL,
  `content` text NOT NULL,
  `published` datetime NOT NULL,
  `link` varchar(256) NOT NULL,
  `author` varchar(45) NOT NULL,
  `video_id` varchar(45) default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `DUPLICATES` (`source_id`,`uri`),
  FULLTEXT KEY `SEARCH` (`title`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `foursquare_data`
--
CREATE TABLE `foursquare_data` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `source_id` int(10) unsigned NOT NULL,
  `guid` varchar(255) NOT NULL,
  `title` text NOT NULL,
  `content` text,
  `link` varchar(255) NOT NULL,
  `published` varchar(45) NOT NULL,
  PRIMARY KEY  USING BTREE (`id`),
  UNIQUE KEY `DUPLICATES` USING BTREE (`source_id`, `guid`),
  FULLTEXT KEY `SEARCH` (`content`,`title`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `github_data`
--
CREATE TABLE `github_data` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `source_id` int(10) unsigned NOT NULL,
  `github_id` varchar(255) NOT NULL,
  `title` text NOT NULL,
  `content` text,
  `repository` text,
  `link` varchar(255) NOT NULL,
  `published` varchar(45) NOT NULL,
  PRIMARY KEY  USING BTREE (`id`),
  UNIQUE KEY `DUPLICATES` USING BTREE (`source_id`, `github_id`),
  FULLTEXT KEY `SEARCH` (`content`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


--
-- Table structure for table `gitorious_data`
--
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

--
-- Table structure for table `goodreads_data`
--
CREATE TABLE `goodreads_data` (
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

--
-- Table structure for table `googlebuzz_data`
--
CREATE TABLE `googlebuzz_data` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `source_id` int(10) unsigned NOT NULL,
  `buzz_id` varchar(255) NOT NULL,
  `title` text NOT NULL,
  `content` text,
  `link` varchar(255) NOT NULL,
  `published` varchar(45) NOT NULL,
  PRIMARY KEY  USING BTREE (`id`),
  UNIQUE KEY `DUPLICATES` USING BTREE (`source_id`, `buzz_id`),
  FULLTEXT KEY `SEARCH` (`content`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `shortUrl`
--
CREATE TABLE `shortUrl` (
  `user_id` int(11) NOT NULL,
  `token`   varchar(16) NOT NULL,
  `url`     varchar(256) NOT NULL,
  `internal` tinyint(1) NOT NULL default '1',
  `created` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`token`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
