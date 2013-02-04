<?php
defined('TINYIB') or exit;

function post_post() {
	global $dbh;

	// get the ip
	$ip = $_SERVER['REMOTE_ADDR'];

	// get the time
	$time = $_SERVER['REQUEST_TIME'];
	$date = make_date($time);

	// get the referrer
	$referrer = isset($_SERVER['HTTP_REFERER'])
		? $_SERVER['HTTP_REFERER']
		: false;

	// set default param flags; don't accept GET values
	$flags = PARAM_DEFAULT ^ PARAM_GET;

	// POST values
	$parent = param('parent', $flags);
	$name = param('field1', $flags);
	$email = param('field2', $flags);
	$subject = param('field3', $flags);
	$comment = param('field4', $flags);

	// We get the password from cookies
	$password = param('password', PARAM_COOKIE | PARAM_STRING);

	// Checks
	if (!ctype_digit($parent)
	|| length($parent) > 10
	|| length($name) > 100
	|| length($email) > 100
	|| length($subject) > 100
	|| length($comment) > 10000
	|| length($password) > 100)
		make_error('Abnormal post.');

	// check if thread exists
	if ($parent && !threadExistsByID($parent))
		make_error('The specified thread does not exist.');

	// check if we're logged in
	$loggedin = check_login();

	if (!$loggedin) {
		checkBanned();
		checkMessageSize();
		checkFlood();
	}

	// make name/tripcode
	list($name, $tripcode) = make_name_tripcode($name);

	// XXX: do formatting
	$comment = str_replace("\n", '<br>',
		htmlspecialchars(trim($comment), ENT_QUOTES, 'UTF-8'));

	// Do file uploads
	$file = handle_upload('file');

	// require a file for new threads
	if (!$parent && !$file)
		make_error('An image is required to start a thread.');
	
	// make sure replies have either a comment or file
	if ($parent && !$file && !length($comment))
		make_error('Please enter a message and/or upload an image to make a reply.');

	// Set up database values
	$post = newPost($parent);

	$post['name'] = $name;
	$post['tripcode'] = $tripcode;
	$post['email'] = $email;
	$post['subject'] = $subject;
	$post['comment'] = $comment;
	$post['password'] = $password;
	$post['date'] = $date;
	$post['time'] = $time;
	$post['ip'] = $ip;

	if ($file) {
		$post['file'] = $file['file'];
		$post['size'] = $file['size'];
		$post['md5'] = $file['md5'];
		$post['origname'] = $file['origname'];
		$post['width'] = $file['width'];
		$post['height'] = $file['height'];
		$post['thumb'] = $file['thumb'];
		$post['t_width'] = $file['t_width'];
		$post['t_height'] = $file['t_height'];
	}

	// Don't commit anything to the database until we say so.
	$dbh->beginTransaction();

	// Insert the post
	$id = insertPost($post);

	if ($file) {
		// Move full image
		move_uploaded_file($file['tmp'], $file['location']);

		if ($file['t_tmp'] === true) {
			// copy full image
			copy($file['location'], $file['t_location']);
		} elseif ($file['t_tmp'] !== false) {
			// move thumbnail
			rename($file['t_tmp'], $file['t_location']);
		}
	}

	// commit changes to database
	$dbh->commit();

	if ($parent) {
		// rebuild thread cache
		rebuildThread($post['parent']);

		// bump the thread if we're not saging
		if (!stristr($email, 'sage'))
			bumpThreadByID($post['parent']);

		$dest = sprintf('res/%d.html#%d', $parent, $id);
	} else {
		// clear old threads
		trimThreads();

		// build thread cache
		rebuildThread($id);

		$dest = sprintf('res/%d.html#%d', $id, $id);
	}

	rebuildIndexes();

	// set password if none is defined
	if ($password === '') {
		$password = random_string();
		$expire = $time + 86400 * 365;
		setcookie('password', $password, $expire, '/');
	}

	// redirect to thread
	redirect(expand_path($dest));
}
