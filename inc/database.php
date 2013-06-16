<?php
defined('TINYIB') or exit;

$db_code = TINYIB_ROOT."/inc/schema/{$db_driver}.php";

if (file_exists($db_code)) {
	require($db_code);
	unset($db_code);
} else {
	throw new Exception("Unknown database type: '$db_driver'.");
}

// Connect to database
$dbh = new Database($db_driver, $db_name, $db_host, $db_username, $db_password);


//
// General shit
//

function boardExists($board) {
	global $dbh, $db_prefix;

	$sth = $dbh->prepare("SELECT 1 FROM `{$db_prefix}_boards` WHERE name = ?");
	$sth->execute(array($board));

	return (bool)$sth->fetchColumn();
}

function tableExists($board) {
	global $dbh, $db_prefix;

	try {
		$dbh->query("SELECT 1 FROM `{$db_prefix}{$board}_posts`");
		return true;
	} catch (Exception $e) { }

	return false;
}


//
// Post functions
//

function postByID($board, $id) {
	global $dbh, $db_prefix;

	$sth = $dbh->prepare("SELECT * FROM `{$db_prefix}{$board}_posts` WHERE id = ?");
	$sth->execute(array($id));

	return $sth->fetch();
}

function threadExistsByID($board, $id) {
	global $dbh, $db_prefix;

	$sth = $dbh->prepare("SELECT 1 FROM `{$db_prefix}{$board}_posts` WHERE id = ? AND NOT parent");
	$sth->execute(array($id));

	return (bool)$sth->fetchColumn();
}

function insertPost($board, $post) {
	global $dbh, $db_prefix;

	$sth = $dbh->prepare("INSERT INTO `{$db_prefix}{$board}_posts` (
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
	) VALUES (null,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
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
	global $dbh, $db_prefix;

	$sth = $dbh->prepare("UPDATE `{$db_prefix}{$board}_posts` SET bumped = ? WHERE id = ?");
	$sth->execute(array($_SERVER['REQUEST_TIME'], $id));
}

function countThreads($board) {
	global $dbh, $db_prefix;

	$sth = $dbh->query("SELECT COUNT(*) FROM `{$db_prefix}{$board}_posts` WHERE `parent` = 0");
	return $sth->fetchColumn();
}

function allThreads($board) {
	global $dbh, $db_prefix;

	$sth = $dbh->query("SELECT * FROM `{$db_prefix}{$board}_posts` WHERE NOT parent ORDER BY bumped DESC");
	return $sth->fetchAll();
}

function getThreads($board, $offset) {
	global $dbh, $db_prefix;

	$sth = $dbh->prepare("SELECT * FROM `{$db_prefix}{$board}_posts` WHERE NOT parent ORDER BY bumped DESC LIMIT ?, 10");
	$sth->bindParam(1, $offset, PDO::PARAM_INT);
	$sth->execute();

	return $sth->fetchAll();
}

function postsInThreadByID($board, $id) {
	global $dbh, $db_prefix;

	if (!$id)
		return false;

	$sth = $dbh->prepare("SELECT * FROM `{$db_prefix}{$board}_posts` WHERE id = :id OR parent = :id ORDER BY id ASC");
	$sth->bindParam(':id', $id, PDO::PARAM_INT);
	$sth->execute();

	return $sth->fetchAll();
}

function latestRepliesInThreadByID($board, $id) {
	global $dbh, $db_prefix;

	$sth = $dbh->prepare("SELECT * FROM `{$db_prefix}{$board}_posts` WHERE parent = ? ORDER BY id DESC LIMIT 3");
	$sth->execute(array($id));

	if ($posts = $sth->fetchAll())
		$posts = array_reverse($posts);

	return $posts;
}

function postByHex($board, $hex) {
	global $dbh, $db_prefix;

	$sth = $dbh->prepare("SELECT id, parent FROM `{$db_prefix}{$board}_posts` WHERE file_hex = ? LIMIT 1");
	$sth->execute(array($hex));

	return $sth->fetch();
}

function latestPosts($board) {
	global $dbh, $db_prefix;

	$sth = $dbh->query("SELECT * FROM `{$db_prefix}{$board}_posts` ORDER BY timestamp DESC LIMIT 10");
	return $sth->fetchAll();
}

function deletePostByID($board, $post) {
	global $dbh, $db_prefix;

	if ($post['parent']) {
		// delete reply
		$sth = $dbh->prepare("DELETE FROM `{$db_prefix}{$board}_posts` WHERE id = ?");
		$sth->execute(array($post['id']));

		return;
	}

	// delete thread
	$sth = $dbh->prepare("DELETE FROM `{$db_prefix}{$board}_posts` WHERE id = ? OR parent = ?");
	$sth->execute(array($post['id'], $post['id']));
}

function getOldThreads($board, $max_threads) {
	global $dbh, $db_prefix;

	if ($max_threads <= 0)
		return array();

	$sth = $dbh->prepare("SELECT id FROM `{$db_prefix}{$board}_posts` WHERE NOT parent ORDER BY bumped DESC LIMIT ?, 1000");
	$sth->bindParam(1, $max_threads, PDO::PARAM_INT);
	$sth->execute();

	return $sth->fetchAll(PDO::FETCH_COLUMN, 0);
}

function lastPostByIP($board) {
	global $dbh, $db_prefix;

	$sth = $dbh->prepare("SELECT * FROM `{$db_prefix}{$board}_posts` WHERE ip = ? ORDER BY id DESC LIMIT 1");
	$sth->execute(array($_SERVER['REMOTE_ADDR']));

	return $sth->fetch();
}


//
// Cross-board functions
//

function getLatestPosts($boards) {
	global $dbh, $db_prefix;

	if (!$boards)
		return array();

	$subqueries = array();
	foreach ($boards as $board)
		$subqueries[] = "SELECT '{$board}' AS board, {$db_prefix}{$board}_posts.* FROM `{$db_prefix}{$board}_posts`";

	$sql = implode(' UNION ALL ', $subqueries).' ORDER BY timestamp DESC LIMIT 25';

	$sth = $dbh->prepare($sql);
	$sth->execute();

	return $sth->fetchAll();
}


//
// Ban functions
//

function banByID($id) {
	global $dbh, $db_prefix;

	$sth = $dbh->prepare("SELECT * FROM `{$db_prefix}_bans` WHERE id = ?");
	$sth->execute(array($id));

	return $sth->fetch();
}

function banByIP($ip) {
	global $dbh, $db_prefix;

	$sth = $dbh->prepare("SELECT * FROM `{$db_prefix}_bans` WHERE ip = ? LIMIT 1");
	$sth->execute(array($ip));

	return $sth->fetch();
}

function allBans() {
	global $dbh, $db_prefix;

	$sth = $dbh->query("SELECT * FROM `{$db_prefix}_bans` ORDER BY timestamp DESC");
	return $sth->fetchAll();
}

function insertBan($ban) {
	global $dbh, $db_prefix;

	$sth = $dbh->prepare("INSERT INTO `{$db_prefix}_bans` (id, ip, timestamp, expire, reason) VALUES (null, :ip, :time, :expire, :reason)");

	$sth->bindParam(':ip', $ban['ip']);
	$sth->bindParam(':time', $_SERVER['REQUEST_TIME']);
	$sth->bindParam(':expire', $ban['expire']);
	$sth->bindParam(':reason', $ban['reason']);

	$sth->execute();

	return $dbh->lastInsertId();
}

function clearExpiredBans() {
	global $dbh, $db_prefix;

	$sth = $dbh->prepare("DELETE FROM `{$db_prefix}_bans` WHERE expire > 0 AND expire <= ?");
	$sth->execute(array($_SERVER['REQUEST_TIME']));
}

function deleteBanByID($id) {
	global $dbh, $db_prefix;

	$sth = $dbh->prepare("DELETE FROM `{$db_prefix}_bans` WHERE id = :id");
	$sth->bindParam(':id', $id, PDO::PARAM_INT);
	$sth->execute();
}

//
// Board functions
//

function createBoard($board, $longname) {
	global $dbh;

	$dbh->beginTransaction();

	createBoardTable($board);
	createBoardEntry($board, $longname);
	createConfigTable($board);

	$dbh->commit();
}

function createBoardEntry($name, $longname) {
	global $dbh, $db_prefix;

	$sth = $dbh->prepare("INSERT INTO `{$db_prefix}_boards` (name, longname, minlevel) VALUES (?, ?, 0)");
	$sth->execute(array($name, $longname));
}

function getBoard($board) {
	global $dbh, $db_prefix;

	$sth = $dbh->prepare("SELECT longname, minlevel FROM `{$db_prefix}_boards` WHERE name = ?");
	$sth->execute(array($board));

	return $sth->fetch();
}

function getAllBoards() {
	global $dbh, $db_prefix;

	$sth = $dbh->query("SELECT name, longname, minlevel FROM `{$db_prefix}_boards` ORDER BY name ASC");

	return $sth->fetchAll();
}

function updateBoard($board, $new_title, $new_level) {
	global $dbh, $db_prefix;

	$sth = $dbh->prepare("UPDATE `{$db_prefix}_boards` SET longname = ?, minlevel = ? WHERE name = ?");
	$sth->execute(array($new_title, $new_level, $board));
}


//
// Flood
//

function checkDuplicateText($comment_hex, $max) {
	global $dbh, $db_prefix;

	$sth = $dbh->prepare("SELECT 1 FROM `{$db_prefix}_flood` WHERE posthash = ? AND time > ?");
	$sth->execute(array($comment_hex, $max));

	return (bool)$sth->fetchColumn();
}

function checkFlood($ip, $max) {
	global $dbh, $db_prefix;

	$sth = $dbh->prepare("SELECT 1 FROM `{$db_prefix}_flood` WHERE ip = ? AND time > ?");
	$sth->execute(array($ip, $max));

	return (bool)$sth->fetchColumn();
}

function checkImageFlood($ip, $max) {
	global $dbh, $db_prefix;

	$sth = $dbh->prepare("SELECT 1 FROM `{$db_prefix}_flood` WHERE imagehash AND ip = ? AND time > ?");
	$sth->execute(array($ip, $max));

	return (bool)$sth->fetchColumn();
}

function insertFloodEntry($entry) {
	global $dbh, $db_prefix;

	$sth = $dbh->prepare("INSERT INTO `{$db_prefix}_flood` (ip, time, imagehash, posthash, isreply) VALUES (?, ?, ?, ?, ?)");
	$sth->execute(array(
		$entry['ip'],
		$entry['time'],
		$entry['imagehash'],
		$entry['posthash'],
		$entry['isreply'],
	));
}


//
// Config
//

function loadConfig($prefix) {
	global $dbh, $db_prefix;

	try {
		$sth = $dbh->query("SELECT * FROM `{$db_prefix}{$prefix}_config`");
	} catch (PDOException $e) {
		// nothing to load
		return false;
	}

	$config = array();
	while ($row = $sth->fetch()) 
		$config[$row['name']] = $row['value'];

	return $config;
}

function saveConfig($prefix, $values) {
	global $dbh, $db_prefix;

	$sql = "REPLACE INTO `{$db_prefix}{$prefix}_config` (name, value) VALUES (?, ?)";

	// We have to do one query per key for db cross-compatability
	foreach ($values as $key => $value) {
		$sth = $dbh->prepare($sql);
		$sth->execute(array($key, $value));
	}
}

function deleteConfigKeys($prefix, $keys) {
	global $dbh, $db_prefix;

	if (!$keys)
		return;

	$sql = "DELETE FROM `{$db_prefix}{$prefix}_config` WHERE name = ?";

	for ($i = count($keys) - 1; $i--;)
		$sql .= " OR name = ?";

	$sth = $dbh->prepare($sql);
	$sth->execute($keys);
}


//
// Users
//

function getUserByID($id) {
	global $dbh, $db_prefix;

	$sth = $dbh->prepare("SELECT * FROM `{$db_prefix}_users` WHERE id = ?");
	$sth->execute(array($id));

	return $sth->fetch();
}

function getUserByName($username) {
	global $dbh, $db_prefix;

	$sth = $dbh->prepare("SELECT * FROM `{$db_prefix}_users` WHERE username = ?");
	$sth->execute(array($username));

	return $sth->fetch();
}

function insertUser($user) {
	global $dbh, $db_prefix;

	$sth = $dbh->prepare("INSERT INTO `{$db_prefix}_users`
	(id, username, password, hashtype, lastlogin, level, email, capcode)
	VALUES (null, ?, ?, ?, ?, ?, ?, ?)");
	$sth->execute(array(
		$user['username'],
		$user['password'],
		$user['hashtype'],
		$user['lastlogin'],
		$user['level'],
		$user['email'],
		$user['capcode'],
	));

	return $dbh->lastInsertID();
}

function modifyUser($user) {
	global $dbh, $db_prefix;

	$values = array();
	$sqlargs = array();

	// generate argument list
	// the array keys are not from user input and are thus safe
	foreach ($user as $key => $value) {
		if ($key !== "id") {
			$sqlargs[] = "$key = ?";
			$values[] = $value;
		}
	}

	// turn $sqlargs into string
	$sqlargs = implode(', ', $sqlargs);

	// must be last
	$values[] = $user['id'];

	$sth = $dbh->prepare("UPDATE `{$db_prefix}_users` SET {$sqlargs} WHERE id = ?");
	$sth->execute($values);
}
