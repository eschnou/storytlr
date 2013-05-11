ALTER TABLE data ADD `is_reply` tinyint(1) not null default '0';
ALTER TABLE stuffpress_data ADD `reply_to_url` varchar(256);