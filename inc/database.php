<?php
defined('TINYIB') or exit;

$dbh = new Database();

// Create the posts table if it does not exist
function createBoardTable($board) {
	global $dbh;

	if (TINYIB_DBMODE === 'mysql') {
		$dbh->query('CREATE TABLE IF NOT EXISTS `'.$board.'` (
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
		) ENGINE=MyISAM');
	} elseif (TINYIB_DBMODE === 'sqlite') {
		$dbh->query('CREATE TABLE IF NOT EXISTS `'.$board.'` (
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
		)');
	}
}

// Create the bans table if it does not exist
if (TINYIB_DBMODE === 'mysql') {
	$dbh->query('CREATE TABLE IF NOT EXISTS `'.TINYIB_DBBANS.'` (
		`id` mediumint(7) unsigned NOT NULL auto_increment,
		`ip` varchar(15) NOT NULL,
		`timestamp` int(20) NOT NULL,
		`expire` int(20) NOT NULL,
		`reason` text NOT NULL,
		PRIMARY KEY (`id`),
		KEY `ip` (`ip`)
	) ENGINE=MyISAM');
} elseif (TINYIB_DBMODE === 'sqlite') {
	$dbh->query('CREATE TABLE IF NOT EXISTS `'.TINYIB_DBBANS.'` (
		id INTEGER PRIMARY KEY,
		ip TEXT NOT NULL,
		timestamp INTEGER NOT NULL,
		expire INTEGER NOT NULL,
		reason TEXT NOT NULL
	)');
}

# Board functions

function boardExists($board) {
	global $dbh;

	try {
		$dbh->query("SELECT 1 FROM `$board`");
		return true;
	} catch (Exception $e) { }

	return false;
}



# Post Functions
function postByID($board, $id) {
	global $dbh;

	$sth = $dbh->prepare("SELECT * FROM `$board` WHERE id = ?");
	$sth->execute(array($id));

	return $sth->fetch();
}

function threadExistsByID($board, $id) {
	global $dbh;

	$sth = $dbh->prepare("SELECT 1 FROM `$board` WHERE id = ? AND NOT parent");
	$sth->execute(array($id));

	return (bool)$sth->fetchColumn();
}

function insertPost($board, $post) {
	global $dbh;

	$sth = $dbh->prepare('INSERT INTO `'.$board.'` (
		id,
		parent,
		timestamp,
		bumped,
		ip,
		name,
		tripcode,
		email,
		date,
		subject,
		message,
		password,
		file,
		file_hex,
		file_original,
		file_size,
		file_size_formatted,
		image_width,
		image_height,
		thumb,
		thumb_width,
		thumb_height
	) VALUES (null,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
	$sth->execute(array(
		$post['parent'],
		$post['time'],
		$post['time'],
		$post['ip'],
		$post['name'],
		$post['tripcode'],
		$post['email'],
		$post['date'],
		$post['subject'],
		$post['comment'],
		$post['password'],
		$post['file'],
		$post['md5'],
		$post['origname'],
		$post['size'],
		$post['prettysize'],
		$post['width'],
		$post['height'],
		$post['thumb'],
		$post['t_width'],
		$post['t_height'],
	));

	return $dbh->lastInsertID();
}

function bumpThreadByID($board, $id) {
	global $dbh;

	$sth = $dbh->prepare("UPDATE `$board` SET bumped = ? WHERE id = ?");
	$sth->execute(array($_SERVER['REQUEST_TIME'], $id));
}

function countThreads($board) {
	global $dbh;

	$sth = $dbh->query("SELECT COUNT(*) FROM `$board` WHERE `parent` = 0");
	return $sth->fetchColumn();
}

function allThreads($board) {
	global $dbh;

	$sth = $dbh->query("SELECT * FROM `$board` WHERE NOT parent ORDER BY bumped DESC");
	return $sth->fetchAll();
}

function getThreads($board, $offset) {
	global $dbh;

	$sth = $dbh->prepare("SELECT * FROM `$board` WHERE NOT parent ORDER BY bumped DESC LIMIT ?, 10");
	$sth->bindParam(1, $offset, PDO::PARAM_INT);
	$sth->execute();

	return $sth->fetchAll();
}

function postsInThreadByID($board, $id) {
	if (!$id)
		return false;

	global $dbh;

	$sth = $dbh->prepare("SELECT * FROM `$board` WHERE id = :id OR parent = :id ORDER BY id ASC");
	$sth->bindParam(':id', $id, PDO::PARAM_INT);
	$sth->execute();

	return $sth->fetchAll();
}

function latestRepliesInThreadByID($board, $id) {
	global $dbh;

	$sth = $dbh->prepare("SELECT * FROM `$board` WHERE parent = ? ORDER BY id DESC LIMIT 3");
	$sth->execute(array($id));

	if ($posts = $sth->fetchAll())
		$posts = array_reverse($posts);

	return $posts;
}

function postByHex($board, $hex) {
	global $dbh;

	$sth = $dbh->prepare("SELECT id, parent FROM `$board` WHERE file_hex = ? LIMIT 1");
	$sth->execute(array($hex));

	return $sth->fetch();
}

function latestPosts($board) {
	global $dbh;

	$sth = $dbh->query("SELECT * FROM `$board` ORDER BY timestamp DESC LIMIT 10");
	return $sth->fetchAll();
}

function deletePostByID($board, $post) {
	global $dbh;

	if ($post['parent']) {
		// delete reply
		$sth = $dbh->prepare("DELETE FROM `$board` WHERE id = ?");
		$sth->execute(array($post['id']));

		return;
	}

	// delete thread
	$sth = $dbh->prepare("DELETE FROM `$board` WHERE id = ? OR parent = ?");
	$sth->execute(array($post['id'], $post['id']));
}

function trimThreads($board) {
	if (TINYIB_MAXTHREADS <= 0)
		return;

	global $dbh;

	$sth = $dbh->prepare("SELECT id FROM `$board` WHERE NOT parent ORDER BY bumped DESC LIMIT ?, 10");
	$sth->bindValue(1, TINYIB_MAXTHREADS, PDO::PARAM_INT);
	$sth->execute();

	while ($row = $sth->fetch()) {
		deletePostByID($row['id']);
	}
}

function lastPostByIP($board) {
	global $dbh;

	$sth = $dbh->prepare("SELECT * FROM `$board` WHERE ip = ? ORDER BY id DESC LIMIT 1");
	$sth->execute(array($_SERVER['REMOTE_ADDR']));

	return $sth->fetch();
}

# Ban Functions
function banByID($id) {
	global $dbh;

	$sth = $dbh->prepare('SELECT * FROM `'.TINYIB_DBBANS.'` WHERE id = ?');
	$sth->execute(array($id));

	return $sth->fetch();
}

function banByIP($ip) {
	global $dbh;
	
	$sth = $dbh->prepare('SELECT * FROM `'.TINYIB_DBBANS.'` WHERE ip = ? LIMIT 1');
	$sth->execute(array($ip));

	return $sth->fetch();
}

function allBans() {
	global $dbh;

	$sth = $dbh->query('SELECT * FROM `'.TINYIB_DBBANS.'` ORDER BY timestamp DESC');
	return $sth->fetchAll();
}

function insertBan($ban) {
	global $dbh;

	$sth = $dbh->prepare('INSERT INTO `'.TINYIB_DBBANS.'`
	(id, ip, timestamp, expire, reason)
	VALUES (null, :ip, :time, :expire, :reason)');

	$sth->bindParam(':ip', $ban['ip']);
	$sth->bindParam(':time', $_SERVER['REQUEST_TIME']);
	$sth->bindParam(':expire', $ban['expire']);
	$sth->bindParam(':reason', $ban['reason']);

	$sth->execute();

	return $dbh->lastInsertId();
}

function clearExpiredBans() {
	global $dbh;

	$sth = $dbh->prepare('DELETE FROM `'.TINYIB_DBBANS.'` WHERE expire > 0 AND expire <= ?');
	$sth->execute(array($_SERVER['REQUEST_TIME']));
}

function deleteBanByID($id) {
	global $dbh;
	
	$sth = $dbh->prepare('DELETE FROM `'.TINYIB_DBBANS.'` WHERE id = :id');
	$sth->bindParam(':id', $id, PDO::PARAM_INT);
	$sth->execute();
}
