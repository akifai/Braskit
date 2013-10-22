<?php
defined('TINYIB') or exit;

// Connect to database
$dbh = new Database($db_name, $db_host, $db_username, $db_password);


//
// General shit
//

function boardExists($board) {
	global $dbh, $db_prefix;

	$sth = $dbh->prepare("SELECT 1 FROM {$db_prefix}boards WHERE name = ?");
	$sth->execute(array($board));

	return (bool)$sth->fetchColumn();
}

function initDatabase() {
	global $dbh, $db_prefix;

	$schema = file_get_contents(TINYIB_ROOT.'/inc/schema.sql');
	$schema = str_replace('/*_*/', $db_prefix, $schema);

	// postgres complains otherwise - this lets us execute multiple queries
	$dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);

	$dbh->query($schema);

	$dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
}

function get_view($admin) {
	if ($admin)
		return 'posts_admin';

	return 'posts_view';
}


//
// Post functions
//

function postByID($board, $id, $admin = false) {
	global $dbh, $db_prefix;

	$view = get_view($admin);

	$sth = $dbh->prepare("SELECT * FROM {$db_prefix}{$view} WHERE board = :board AND id = :id");

	$sth->bindParam(':board', $board);
	$sth->bindParam(':id', $id);

	$sth->execute();

	$sth->setFetchMode(PDO::FETCH_CLASS, 'Post');

	return $sth->fetch();
}

function threadExistsByID($board, $id) {
	global $dbh, $db_prefix;

	$sth = $dbh->prepare("SELECT 1 FROM {$db_prefix}posts WHERE board = :board AND id = :id AND parent = 0");

	$sth->bindParam(':board', $board);
	$sth->bindParam(':id', $id);

	$sth->execute();

	return (bool)$sth->fetchColumn();
}

function insertPost($post) {
	global $dbh, $db_prefix;

	$sth = $dbh->prepare("INSERT INTO {$db_prefix}posts (parent, board, timestamp, lastbump, ip, name, tripcode, email, subject, comment, password, file, md5, origname, filesize, prettysize, width, height, thumb, t_width, t_height) VALUES (?, ?, to_timestamp(?), to_timestamp(?), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) RETURNING id");
	$sth->execute(array(
		$post->parent,
		$post->board,
		$post->time,
		$post->time,
		$post->ip,
		$post->name,
		$post->tripcode,
		$post->email,
		$post->subject,
		$post->comment,
		$post->password,
		$post->file,
		$post->md5,
		$post->origname,
		$post->size,
		$post->prettysize,
		$post->width,
		$post->height,
		$post->thumb,
		$post->t_width,
		$post->t_height,
	));

	// Return the ID of the post - PDO::lastInsertID() isn't used because
	// id doesn't (and cannot) have a sequence object!
	return $sth->fetchColumn();
}

function bumpThreadByID($board, $id) {
	global $dbh, $db_prefix;

	$sth = $dbh->prepare("UPDATE {$db_prefix}posts SET lastbump = to_timestamp(?) WHERE id = ?");
	$sth->execute(array($_SERVER['REQUEST_TIME'], $id));
}

function countThreads($board) {
	global $dbh, $db_prefix;

	$sth = $dbh->prepare("SELECT COUNT(*) FROM {$db_prefix}posts WHERE board = ? AND parent = 0");
	$sth->execute(array($board));

	return $sth->fetchColumn();
}

function allThreads($board, $admin = false) {
	global $dbh, $db_prefix;

	$view = get_view($admin);

	$sth = $dbh->prepare("SELECT * FROM {$db_prefix}{$view} WHERE board = ? AND parent = 0 ORDER BY lastbump DESC");
	$sth->execute(array($board));

	return $sth->fetchAll(PDO::FETCH_CLASS, 'Post');
}

function getThreads($board, $offset, $limit, $admin = false) {
	global $dbh, $db_prefix;

	$view = get_view($admin);

	$sql = "SELECT * FROM {$db_prefix}{$view} WHERE board = :board AND parent = 0 ORDER BY lastbump DESC";

	if ($limit)
		$sql .= ' LIMIT :limit OFFSET :offset';

	$sth = $dbh->prepare($sql);
	$sth->bindParam(':board', $board);

	if ($limit) {
		$sth->bindParam(':limit', $limit, PDO::PARAM_INT);
		$sth->bindParam(':offset', $offset, PDO::PARAM_INT);
	}

	$sth->execute();

	return $sth->fetchAll(PDO::FETCH_CLASS, 'Post');
}

function postsInThreadByID($board, $id, $admin = false) {
	global $dbh, $db_prefix;

	if (!$id)
		return false;

	$view = get_view($admin);

	$sth = $dbh->prepare("SELECT * FROM {$db_prefix}{$view} WHERE board = :board AND (id = :id OR parent = :id) ORDER BY id ASC");

	$sth->bindParam(':board', $board, PDO::PARAM_STR);
	$sth->bindParam(':id', $id, PDO::PARAM_INT);

	$sth->execute();

	return $sth->fetchAll(PDO::FETCH_CLASS, 'Post');
}

function countPostsInThread($board, $id) {
	global $dbh, $db_prefix;

	$sth = $dbh->prepare("SELECT COUNT(*) FROM {$db_prefix}posts WHERE board = :board AND (id = :id OR parent = :id)");

	$sth->bindParam(':board', $board);
	$sth->bindParam(':id', $id);

	$sth->execute();

	return $sth->fetchColumn();
}

function latestRepliesInThreadByID($board, $id, $limit, $admin = false) {
	global $dbh, $db_prefix;

	$view = get_view($admin);

	$sth = $dbh->prepare("SELECT * FROM {$db_prefix}{$view} WHERE board = :board AND parent = :id ORDER BY id DESC LIMIT :limit");
	$sth->bindParam(':board', $board);
	$sth->bindParam(':id', $id, PDO::PARAM_INT);
	$sth->bindParam(':limit', $limit, PDO::PARAM_INT);
	$sth->execute();

	$posts = $sth->fetchAll(PDO::FETCH_CLASS, 'Post');

	if ($posts) {
		// get the replies in the right order
		$posts = array_reverse($posts);
	}

	return $posts;
}

function postByMD5($board, $md5) {
	global $dbh, $db_prefix;

	$sth = $dbh->prepare("SELECT * FROM {$db_prefix}posts WHERE board = :board AND md5 = :md5 LIMIT 1");
	$sth->bindParam(':board', $board, PDO::PARAM_STR);
	$sth->bindParam(':md5', $md5, PDO::PARAM_STR);
	$sth->execute();

	$sth->setFetchMode(PDO::FETCH_CLASS, 'Post');

	return $sth->fetch();
}

function deletePostByID($board, $post) {
	global $dbh, $db_prefix;

	if ($post->parent) {
		// delete reply
		$sth = $dbh->prepare("DELETE FROM {$db_prefix}posts WHERE board = ? AND id = ?");
		$sth->execute(array($board, $post->id));

		return;
	}

	// delete thread
	$sth = $dbh->prepare("DELETE FROM {$db_prefix}posts WHERE board = ? AND (id = ? OR parent = ?)");
	$sth->execute(array($board, $post->id, $post->id));
}

function getOldThreads($board, $max_threads) {
	global $dbh, $db_prefix;

	if ($max_threads <= 0)
		return array();

	$sth = $dbh->prepare("SELECT id FROM {$db_prefix}posts WHERE board = :board AND parent = 0 ORDER BY lastbump DESC LIMIT :limit OFFSET 1000");
	$sth->bindParam(':board', $board);
	$sth->bindParam(':limit', $max_threads, PDO::PARAM_INT);
	$sth->execute();

	return $sth->fetchAll(PDO::FETCH_COLUMN, 0);
}


//
// Cross-board functions
//

function getLatestPosts($limit, $admin = false) {
	global $dbh, $db_prefix;

	if ($limit < 1)
		$limit = 1;

	$view = get_view($admin);

	$sth = $dbh->prepare("SELECT * FROM {$db_prefix}{$view} ORDER BY id DESC LIMIT :limit");
	$sth->bindParam(':limit', $limit, PDO::PARAM_INT);
	$sth->execute();

	return $sth->fetchAll(PDO::FETCH_CLASS, 'Post');
}


//
// Ban functions
//

function banByID($id) {
	global $dbh, $db_prefix;

	$sth = $dbh->prepare("SELECT * FROM {$db_prefix}bans_view WHERE id = ?");
	$sth->execute(array($id));

	return $sth->fetch();
}

function bansByIP($ip) {
	global $dbh, $db_prefix;

	$sth = $dbh->prepare("SELECT * FROM {$db_prefix}bans_view WHERE ip >>= ? ORDER BY timestamp DESC");
	$sth->execute(array($ip));

	return $sth->fetchAll(PDO::FETCH_CLASS, 'Ban');
}

function activeBansByIP($ip, $time) {
	global $dbh, $db_prefix;

	$sth = $dbh->prepare("SELECT * FROM {$db_prefix}bans_view WHERE ip >>= :ip AND (expire IS NULL OR expire > to_timestamp(:time)) ORDER BY timestamp DESC");

	$sth->bindParam(':ip', $ip, PDO::PARAM_STR);
	$sth->bindParam(':time', $time, PDO::PARAM_INT);

	$sth->execute();

	return $sth->fetchAll(PDO::FETCH_CLASS, 'Ban');
}

function allBans() {
	global $dbh, $db_prefix;

	$sth = $dbh->query("SELECT * FROM {$db_prefix}bans_view ORDER BY timestamp DESC");
	return $sth->fetchAll(PDO::FETCH_CLASS, 'Ban');
}

function insertBan(Ban $ban) {
	global $dbh, $db_prefix;

	$sth = $dbh->prepare("INSERT INTO {$db_prefix}bans (ip, timestamp, expire, reason) VALUES (network(:ip), to_timestamp(:time), to_timestamp(:expire), :reason)");

	$sth->bindParam(':ip', $ban->ip);
	$sth->bindParam(':time', $ban->timestamp);
	$sth->bindParam(':expire', $ban->expire);
	$sth->bindParam(':reason', $ban->reason);

	$sth->execute();

	return $dbh->lastInsertID($db_prefix.'bans_id_seq');
}

function clearExpiredBans() {
	global $dbh, $db_prefix;

	$sth = $dbh->prepare("DELETE FROM {$db_prefix}bans WHERE expire > 0 AND expire <= ?");
	$sth->execute(array($_SERVER['REQUEST_TIME']));
}

function deleteBanByID($id) {
	global $dbh, $db_prefix;

	$sth = $dbh->prepare("DELETE FROM {$db_prefix}bans WHERE id = :id");
	$sth->bindParam(':id', $id, PDO::PARAM_INT);
	$sth->execute();
}

//
// Board functions
//

function createBoard($board, $longname) {
	global $dbh, $db_prefix;

	$sth = $dbh->prepare("INSERT INTO {$db_prefix}boards (name, longname, minlevel, lastid) VALUES (?, ?, 0, 0)");
	$sth->execute(array($board, $longname));
}

function getBoard($board) {
	global $dbh, $db_prefix;

	$sth = $dbh->prepare("SELECT longname, minlevel FROM {$db_prefix}boards WHERE name = ?");
	$sth->execute(array($board));

	return $sth->fetch();
}

function getAllBoards() {
	global $dbh, $db_prefix;

	$sth = $dbh->query("SELECT name, longname, minlevel FROM {$db_prefix}boards ORDER BY name ASC");

	return $sth->fetchAll();
}

function renameBoard($oldname, $newname) {
	global $dbh, $db_prefix;

	// since we're using cascading and foreign keys, pgsql will handle the
	// other tables containing board names for us automagically.
	$sth = $dbh->prepare("UPDATE {$db_prefix}boards SET name = ? WHERE name = ?");
	$sth->execute(array($newname, $oldname));
}

function updateBoard($board, $new_title, $new_level) {
	global $dbh, $db_prefix;

	$sth = $dbh->prepare("UPDATE {$db_prefix}boards SET longname = ?, minlevel = ? WHERE name = ?");
	$sth->execute(array($new_title, $new_level, $board));
}


//
// Flood
//

function checkDuplicateText($comment_hex, $max) {
	global $dbh, $db_prefix;

	$sth = $dbh->prepare("SELECT 1 FROM {$db_prefix}flood WHERE posthash = ? AND timestamp > to_timestamp(?)");
	$sth->execute(array($comment_hex, $max));

	return (bool)$sth->fetchColumn();
}

function checkFlood($ip, $max) {
	global $dbh, $db_prefix;

	$sth = $dbh->prepare("SELECT 1 FROM {$db_prefix}flood WHERE ip = ? AND timestamp > to_timestamp(?)");
	$sth->execute(array($ip, $max));

	return (bool)$sth->fetchColumn();
}

function checkImageFlood($ip, $max) {
	global $dbh, $db_prefix;

	$sth = $dbh->prepare("SELECT 1 FROM {$db_prefix}flood WHERE imagehash IS NOT NULL AND ip = ? AND timestamp > to_timestamp(?)");
	$sth->execute(array($ip, $max));

	return (bool)$sth->fetchColumn();
}

function insertFloodEntry($entry) {
	global $dbh, $db_prefix;

	$entry['imagehash'] = $entry['imagehash'] ?: null;
	$entry['posthash'] = $entry['posthash'] ?: null;

	$sth = $dbh->prepare("INSERT INTO {$db_prefix}flood (ip, timestamp, imagehash, posthash, isreply) VALUES (:ip, to_timestamp(:time), :imagehash, :posthash, :isreply)");

	$sth->bindParam(':ip', $entry['ip'], PDO::PARAM_STR);
	$sth->bindParam(':time', $entry['time'], PDO::PARAM_INT);
	$sth->bindParam(':imagehash', $entry['imagehash'], PDO::PARAM_STR);
	$sth->bindParam(':posthash', $entry['posthash'], PDO::PARAM_STR);
	$sth->bindParam(':isreply', $entry['isreply'], PDO::PARAM_BOOL);

	$sth->execute();
}


//
// Config
//

function loadConfig($board) {
	global $dbh, $db_prefix;

	if ($board === null) {
		$part = 'IS NULL';
		$args = array();
	} else {
		$part = '= ?';
		$args = array($board);
	}

	try {
		$sth = $dbh->prepare("SELECT * FROM {$db_prefix}config WHERE board $part");
		$sth->execute($args);
	} catch (PDOException $e) {
		// nothing to load
		return false;
	}

	$config = array();
	while ($row = $sth->fetch()) 
		$config[$row['name']] = $row['value'];

	return $config;
}

function saveConfig($board, $values) {
	global $dbh, $db_prefix;

	$sth = $dbh->prepare("SELECT upsert_config(?, ?, ?)");

	foreach ($values as $key => $value) {
		$sth->execute(array($key, $value, $board));
	}
}

function deleteConfigKeys($board, $keys) {
	global $dbh, $db_prefix;

	if (!$keys)
		return;

	if ($board === null) {
		$part = 'IS NULL';
	} else {
		$part = '= ?';
	}

	$sth = $dbh->prepare("DELETE FROM {$db_prefix}config WHERE board $part AND name = ?");

	if ($board === null) {
		foreach ($keys as $key)
			$sth->execute(array($key));
	} else {
		foreach ($keys as $key)
			$sth->execute(array($board, $key));
	}
}


//
// Reporting
//

function countReports() {
	global $dbh, $db_prefix;

	$sth = $dbh->query("SELECT COUNT(*) FROM {$db_prefix}reports");

	return $sth->fetchColumn();
}

function getReports() {
	global $dbh, $db_prefix;

	$sth = $dbh->prepare("SELECT * FROM {$db_prefix}reports ORDER BY id");
	$sth->execute(array());

	return $sth->fetch();
}

function getReportsByIP() {
	global $dbh, $db_prefix;

	$sth = $dbh->prepare("SELECT * FROM {$db_prefix}reports ORDER BY id WHERE ip << ?");
	$sth->execute(array($ip));

	return $sth->fetch();
}

function insertReports($posts, $report) {
	global $dbh, $db_prefix;

	$sth = $dbh->prepare("INSERT INTO {$db_prefix}reports (postid, board, ip, timestamp, reason) VALUES (:id, :board, :ip, to_timestamp(:time), :reason) RETURNING id");
	$sth->bindParam(':board', $report['board']);
	$sth->bindParam(':ip', $report['ip']);
	$sth->bindParam(':time', $report['time']);
	$sth->bindParam(':reason', $report['reason']);

	$report_ids = array();

	foreach ($posts as $post) {
		$sth->bindParam(':id', $post->id);
		$report_ids[] = $sth->execute();
	}

	return $report_ids;
}

function checkReportFlood($ip, $max) {
	global $dbh, $db_prefix;

	$sth = $dbh->prepare("SELECT COUNT(*) FROM {$db_prefix}reports WHERE ip <<= :ip AND timestamp > to_timestamp(:time)");
	$sth->bindParam(':ip', $ip);
	$sth->bindParam(':time', $max);
	$sth->execute();

	// the user is flooding if true
	return (bool)$sth->fetchColumn();
}


//
// Spam
//

function getLatestSpamRules() {
	global $dbh, $db_prefix;

	$sth = $dbh->prepare("SELECT * FROM {$db_prefix}spam ORDER BY id DESC LIMIT 1");
	$sth->execute();

	return $sth->fetch();
}


//
// Users
//

function getUserList() {
	global $dbh, $db_prefix;

	$sth = $dbh->query("SELECT username, level, lastlogin, email FROM {$db_prefix}users ORDER BY level DESC, username");

	return $sth->fetchAll();
}

function getUser($username) {
	global $dbh, $db_prefix;

	$sth = $dbh->prepare("SELECT * FROM {$db_prefix}users WHERE username = ?");
	$sth->execute(array($username));

	return $sth->fetch();
}

function insertUser($user) {
	global $dbh, $db_prefix;

	$sth = $dbh->prepare("INSERT INTO {$db_prefix}users (username, password, hashtype, level, email, capcode) VALUES (?, ?, ?, ?, ?, ?)");
	$sth->execute(array($user['username'], $user['password'], $user['hashtype'], $user['level'], $user['email'], $user['capcode']));
}

function modifyUser($user) {
	global $dbh, $db_prefix;

	$values = array();
	$sqlargs = array();

	// generate argument list
	// the array keys are not from user input and are thus safe
	foreach ($user as $key => $value) {
		$sqlargs[] = "$key = ?";
		$values[] = $value;
	}

	// turn $sqlargs into string
	$sqlargs = implode(', ', $sqlargs);

	// must be last
	$values[] = $user['username'];

	$sth = $dbh->prepare("UPDATE {$db_prefix}users SET {$sqlargs} WHERE username = ?");
	$sth->execute($values);
}

function deleteUser($username) {
	global $dbh, $db_prefix;

	$sth = $dbh->prepare("DELETE FROM {$db_prefix}users WHERE username = ?");
	$sth->execute(array($username));
}
