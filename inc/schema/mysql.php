<?php
defined('TINYIB') or exit;

function createBoardTable($board) {
	global $dbh, $db_prefix;

	$sql = <<<EOSQL
CREATE TABLE {$db_prefix}{$board} (
	`id` mediumint(7) unsigned NOT NULL auto_increment,
	`parent` mediumint(7) unsigned NOT NULL,
	`timestamp` int(20) NOT NULL,
	`bumped` int(20) NOT NULL,
	`ip` varchar(15) NOT NULL,
	`name` text NOT NULL,
	`tripcode` text NOT NULL,
	`email` text NOT NULL,
	`date` text NOT NULL,
	`subject` text NOT NULL,
	`message` text NOT NULL,
	`password` text NOT NULL,
	`file` text NOT NULL,
	`file_hex` varchar(32) NOT NULL,
	`file_original` text NOT NULL,
	`file_size` int(20) unsigned NOT NULL default "0",
	`file_size_formatted` varchar(75) NOT NULL,
	`image_width` smallint(5) unsigned NOT NULL default "0",
	`image_height` smallint(5) unsigned NOT NULL default "0",
	`thumb` varchar(255) NOT NULL,
	`thumb_width` smallint(5) unsigned NOT NULL default "0",
	`thumb_height` smallint(5) unsigned NOT NULL default "0",
	PRIMARY KEY (`id`),
	KEY `parent` (`parent`),
	KEY `bumped` (`bumped`)
) ENGINE=MyISAM;
EOSQL;

	$dbh->query($sql);
}

function createBansTable() {
	global $dbh, $db_prefix;

	$sql = <<<EOSQL
CREATE TABLE {$db_prefix}_bans (
	`id` mediumint(7) unsigned NOT NULL auto_increment,
	`ip` varchar(15) NOT NULL,
	`timestamp` int(20) NOT NULL,
	`expire` int(20) NOT NULL,
	`reason` text NOT NULL,
	PRIMARY KEY (`id`),
	KEY `ip` (`ip`)
) ENGINE=MyISAM;
EOSQL;

	$dbh->query($sql);
}
