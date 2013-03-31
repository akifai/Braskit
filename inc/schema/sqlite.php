<?php
defined('TINYIB') or exit;

function createBoardTable($board) {
	global $dbh, $db_prefix;

	$sql = <<<EOSQL
CREATE TABLE IF NOT EXISTS {$db_prefix}{$board} (
	id INTEGER PRIMARY KEY,
	parent INTEGER NOT NULL,
	timestamp INTEGER NOT NULL,
	bumped INTEGER NOT NULL,
	ip TEXT NOT NULL,
	name TEXT NOT NULL,
	tripcode TEXT NOT NULL,
	email TEXT NOT NULL,
	date TEXT NOT NULL,
	subject TEXT NOT NULL,
	message TEXT NOT NULL,
	password TEXT NOT NULL,
	file TEXT NOT NULL,
	file_hex TEXT NOT NULL,
	file_original text NOT NULL,
	file_size INTEGER NOT NULL DEFAULT "0",
	file_size_formatted TEXT NOT NULL,
	image_width INTEGER NOT NULL DEFAULT "0",
	image_height INTEGER NOT NULL DEFAULT "0",
	thumb TEXT NOT NULL,
	thumb_width INTEGER NOT NULL DEFAULT "0",
	thumb_height INTEGER NOT NULL DEFAULT "0"
);
EOSQL;

	$dbh->query($sql);
}

function createBansTable() {
	global $dbh, $db_prefix;

	$sql = <<<EOSQL
CREATE TABLE IF NOT EXISTS {$db_prefix}_bans (
	id INTEGER PRIMARY KEY,
	ip TEXT NOT NULL,
	timestamp INTEGER NOT NULL,
	expire INTEGER NOT NULL,
	reason TEXT NOT NULL
);
EOSQL;

	$dbh->query($sql);
}
