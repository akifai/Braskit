<?php
defined('TINYIB') or exit;

// TODO: table prefixes

//
// General shit
//

function boardExists($board) {
	global $dbh;

	$sth = $dbh->prepare("SELECT 1 FROM `_boards` WHERE name = ?");
	$sth->execute(array($board));

	return (bool)$sth->fetchColumn();
}

function tableExists($board) {
	global $dbh;

	try {
		$dbh->query("SELECT 1 FROM `${board}_posts`");
		return true;
	} catch (Exception $e) { }

	return false;
}


//
// Post functions
//

function postByID($board, $id) {
	global $dbh;

	$sth = $dbh->prepare("SELECT * FROM `${board}_posts` WHERE id = ?");
	$sth->execute(array($id));

	return $sth->fetch();
}

function threadExistsByID($board, $id) {
	global $dbh;

	$sth = $dbh->prepare("SELECT 1 FROM `${board}_posts` WHERE id = ? AND NOT parent");
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

	$sth = $dbh->prepare("UPDATE `${board}_posts` SET bumped = ? WHERE id = ?");
	$sth->execute(array($_SERVER['REQUEST_TIME'], $id));
}

function countThreads($board) {
	global $dbh;

	$sth = $dbh->query("SELECT COUNT(*) FROM `${board}_posts` WHERE `parent` = 0");
	return $sth->fetchColumn();
}

function allThreads($board) {
	global $dbh;

	$sth = $dbh->query("SELECT * FROM `${board}_posts` WHERE NOT parent ORDER BY bumped DESC");
	return $sth->fetchAll();
}

function getThreads($board, $offset) {
	global $dbh;

	$sth = $dbh->prepare("SELECT * FROM `${board}_posts` WHERE NOT parent ORDER BY bumped DESC LIMIT ?, 10");
	$sth->bindParam(1, $offset, PDO::PARAM_INT);
	$sth->execute();

	return $sth->fetchAll();
}

function postsInThreadByID($board, $id) {
	if (!$id)
		return false;

	global $dbh;

	$sth = $dbh->prepare("SELECT * FROM `${board}_posts` WHERE id = :id OR parent = :id ORDER BY id ASC");
	$sth->bindParam(':id', $id, PDO::PARAM_INT);
	$sth->execute();

	return $sth->fetchAll();
}

function latestRepliesInThreadByID($board, $id) {
	global $dbh;

	$sth = $dbh->prepare("SELECT * FROM `${board}_posts` WHERE parent = ? ORDER BY id DESC LIMIT 3");
	$sth->execute(array($id));

	if ($posts = $sth->fetchAll())
		$posts = array_reverse($posts);

	return $posts;
}

function postByHex($board, $hex) {
	global $dbh;

	$sth = $dbh->prepare("SELECT id, parent FROM `${board}_posts` WHERE file_hex = ? LIMIT 1");
	$sth->execute(array($hex));

	return $sth->fetch();
}

function latestPosts($board) {
	global $dbh;

	$sth = $dbh->query("SELECT * FROM `${board}_posts` ORDER BY timestamp DESC LIMIT 10");
	return $sth->fetchAll();
}

function deletePostByID($board, $post) {
	global $dbh;

	if ($post['parent']) {
		// delete reply
		$sth = $dbh->prepare("DELETE FROM `${board}_posts` WHERE id = ?");
		$sth->execute(array($post['id']));

		return;
	}

	// delete thread
	$sth = $dbh->prepare("DELETE FROM `${board}_posts` WHERE id = ? OR parent = ?");
	$sth->execute(array($post['id'], $post['id']));
}

function trimThreads($board) {
	if (TINYIB_MAXTHREADS <= 0)
		return;

	global $dbh;

	$sth = $dbh->prepare("SELECT id FROM `${board}_posts` WHERE NOT parent ORDER BY bumped DESC LIMIT ?, 10");
	$sth->bindValue(1, TINYIB_MAXTHREADS, PDO::PARAM_INT);
	$sth->execute();

	while ($row = $sth->fetch()) {
		deletePostByID($row['id']);
	}
}

function lastPostByIP($board) {
	global $dbh;

	$sth = $dbh->prepare("SELECT * FROM `${board}_posts` WHERE ip = ? ORDER BY id DESC LIMIT 1");
	$sth->execute(array($_SERVER['REMOTE_ADDR']));

	return $sth->fetch();
}


//
// Ban functions
//

function banByID($id) {
	global $dbh;

	$sth = $dbh->prepare('SELECT * FROM _bans WHERE id = ?');
	$sth->execute(array($id));

	return $sth->fetch();
}

function banByIP($ip) {
	global $dbh;
	
	$sth = $dbh->prepare('SELECT * FROM _bans WHERE ip = ? LIMIT 1');
	$sth->execute(array($ip));

	return $sth->fetch();
}

function allBans() {
	global $dbh;

	$sth = $dbh->query('SELECT * FROM _bans ORDER BY timestamp DESC');
	return $sth->fetchAll();
}

function insertBan($ban) {
	global $dbh;

	$sth = $dbh->prepare('INSERT INTO _bans
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

	$sth = $dbh->prepare('DELETE FROM _bans WHERE expire > 0 AND expire <= ?');
	$sth->execute(array($_SERVER['REQUEST_TIME']));
}

function deleteBanByID($id) {
	global $dbh;
	
	$sth = $dbh->prepare('DELETE FROM _bans WHERE id = :id');
	$sth->bindParam(':id', $id, PDO::PARAM_INT);
	$sth->execute();
}

//
// Board functions
//

function createBoardTables($board) {
	createBoardTable($board);
	createBoardConfigTable($board);
}

function createBoardEntry($name, $longname) {
	global $dbh;

	$sth = $dbh->prepare('INSERT INTO `_boards` (name, longname, minlevel)
	VALUES (?, ?, 0)');
	$sth->execute(array($name, $longname));
}
