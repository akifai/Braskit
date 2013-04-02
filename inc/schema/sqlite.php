<?php
defined('TINYIB') or exit;

//
// Board table
//

function createBoardTable($board) {
	global $dbh, $db_prefix;

	$sql = <<<EOSQL
CREATE TABLE {$db_prefix}{$board}_posts (
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


//
// Board config table
//

function createBoardConfigTable($board) {
	global $dbh, $db_prefix;

	$sql = <<<EOSQL
CREATE TABLE {$db_prefix}{$board}_config (
	name TEXT PRIMARY KEY,
	value TEXT
);
EOSQL;

	$dbh->query($sql);
}


//
// Bans table
//

function createBansTable() {
	global $dbh, $db_prefix;

	$sql = <<<EOSQL
CREATE TABLE {$db_prefix}_bans (
	id INTEGER PRIMARY KEY,
	ip TEXT NOT NULL,
	timestamp INTEGER NOT NULL,
	expire INTEGER NOT NULL,
	reason TEXT NOT NULL
);
EOSQL;

	$dbh->query($sql);
}


//
// Board list table
//

function createBoardsTable() {
	global $dbh, $db_prefix;

	$sql = <<<EOSQL
CREATE TABLE {$db_prefix}_boards (
	name TEXT PRIMARY KEY,
	longname TEXT NOT NULL,
	minlevel INTEGER NOT NULL
);
EOSQL;

	$dbh->query($sql);
}


//
// Flood table
//

function createFloodTable() {
	global $dbh, $db_prefix;

	$sql = <<<EOSQL
CREATE TABLE {$db_prefix}_flood (
	id INTEGER PRIMARY KEY,
	time INTEGER NOT NULL,
	imagehash TEXT NOT NULL,
	posthash TEXT NOT NULL,
	isreply INTEGER NOT NULL,
	image INTEGER NOT NULL
);
EOSQL;

	$dbh->query($sql);
}


//
// User table
//

function createUserTable() {
	global $dbh, $db_prefix;

	$sql = <<<EOSQL
CREATE TABLE {$db_prefix}_users (
	id INTEGER PRIMARY KEY,
	username TEXT UNIQUE NOT NULL,
	password TEXT NOT NULL,
	hashtype INTEGER NOT NULL,
	lastlogin INTEGER NOT NULL,
	level INTEGER NOT NULL,
	email TEXT NOT NULL,
	capcode TEXT NOT NULL
);
EOSQL;

	$dbh->query($sql);
}


// boilerplate code for easy copypasting

/*
function createXxxTable() {
	global $dbh, $db_prefix;

	$sql = <<<EOSQL
CREATE TABLE {$db_prefix}_xxx (
	id INTEGER PRIMARY KEY,
);
EOSQL;

	$dbh->query($sql);
}
*/
