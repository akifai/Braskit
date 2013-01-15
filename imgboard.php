<?php
# TinyIB
#
# https://github.com/tslocum/TinyIB

ini_set('display_errors', true);
error_reporting(E_ALL);
session_start();

require 'settings.php';
require 'inc/database.php';
require 'inc/functions.php';
require 'inc/html.php';

// Unescape magic quotes
if (get_magic_quotes_gpc()) {
	$process = array(&$_GET, &$_POST, &$_COOKIE, &$_REQUEST);
	while (list($key, $val) = each($process)) {
		foreach ($val as $k => $v) {
			unset($process[$key][$k]);
			if (is_array($v)) {
				$process[$key][stripslashes($k)] = $v;
				$process[] = &$process[$key][stripslashes($k)];
				continue;
			}

			$process[$key][stripslashes($k)] = stripslashes($v);
		}
	}
	unset($process);
}

function make_error($message, $no_template = false, $trace = false) {
	// Create a stack trace on request
	if ($trace === true)
		$trace = debug_backtrace();

	// Error messages using Twig
	if (!$no_template) {
		$referrer = @$_SERVER['HTTP_REFERER'];
		if ($trace)
			$trace = var_export($trace, true);

		echo render('error.html', array(
			'message' => $message,
			'stack_trace' => $trace,
			'referrer' => $referrer,
		));

		exit;
	}

	echo '<h1>Error</h1><pre>';
	echo nl2br(htmlspecialchars($message));
	echo '</pre>';

	// Print stack trace
	if ($trace !== false) {
		echo '<textarea cols="80" rows="25" readonly>';
		echo htmlspecialchars(var_export($trace, true));
		echo '</textarea>';
	}

	// Return link
	echo '<p>[<a href="'.get_script_name().'">Return</a>]</p>';

	exit;
}

if (!file_exists('settings.php')) {
	make_error('Please rename the file settings.default.php to settings.php');
}

if (TINYIB_TRIPSEED == '' || TINYIB_ADMINPASS == '') {
	make_error('TINYIB_TRIPSEED and TINYIB_ADMINPASS must be configured');
}

// Check directories are writable by the script
foreach (array('res', 'src', 'thumb') as $dir) {
	if (!is_writable($dir))
		make_error("Directory '" . $dir . "' can not be written to.  Please modify its permissions.");
}

// Check if the request is to make a post
if (isset($_POST['field4']) || isset($_POST['file'])) {
	list($loggedin, $isadmin) = manageCheckLogIn();
	$rawpost = isRawPost();
	if (!$loggedin) {
		checkBanned();
		checkMessageSize();
		checkFlood();
	}

	$post = newPost(setParent());
	$post['ip'] = $_SERVER['REMOTE_ADDR'];

	list($post['name'], $post['tripcode']) = make_name_tripcode($_POST['field1']);

	$post['name'] = substr($post['name'], 0, 75);
	$post['email'] = substr($_POST['field2'], 0, 75);
	$post['subject'] = substr($_POST['field3'], 0, 75);

	if ($rawpost) {
		//$rawposttext = ($isadmin) ? ' <span style="color: red;">## Admin</span>' : ' <span style="color: purple;">## Mod</span>';
		$post['message'] = $_POST['field4']; // Treat message as raw HTML
	} else {
		$rawposttext = '';
		$post['message'] = str_replace("\n", '<br>', colorQuote(postLink(cleanString(rtrim($_POST['field4'])))));
	}

	$post['password'] = ($_POST['password'] != '') ? md5(md5($_POST['password'])) : '';
	$post['date'] = make_date(time());

	if (isset($_FILES['file']) && $_FILES['file']['name'] != "") {
		require 'inc/image.php';

		validateFileUpload();

		$tmp_name = $_FILES['file']['tmp_name'];

		if (!is_uploaded_file($tmp_name))
			make_error("File transfer failure. Please retry the submission.");

		if ((TINYIB_MAXKB > 0) && (filesize($tmp_name) > (TINYIB_MAXKB * 1024)))
			make_error("That file is larger than " . TINYIB_MAXKBDESC . ".");

		if (($info = analyse_image($tmp_name)) === false)
			make_error("Failed to read the size of the uploaded file. Please retry the submission.");

		$post['file_original'] = $_FILES['file']['name'];
		$post['file_hex'] = md5_file($tmp_name);
		$post['file_size'] = $_FILES['file']['size'];
		$post['file_size_formatted'] = convertBytes($post['file_size']);
		$file_name = time().substr(microtime(), 2, 3);
		$post['file'] = sprintf('%s.%s', $file_name, $info['ext']);
		$post['thumb'] = sprintf('%ss.%s', $file_name, $info['ext']);

		if (!in_array($info['ext'], array('jpg', 'gif', 'png')))
			make_error("Only GIF, JPG, and PNG files are allowed.");

		$file_location = "src/" . $post['file'];
		$thumb_location = "thumb/" . $post['thumb'];

		checkDuplicateImage($post['file_hex']);

		if (!move_uploaded_file($tmp_name, $file_location))
			make_error("Could not copy uploaded file.");

		$thumb_size = make_thumb_size(
			$info['width'],
			$info['height'],
			TINYIB_MAXW,
			TINYIB_MAXH
		);

		if ($thumb_size === false) {
			copy($file_location, $thumb_location);
			$post['thumb_width'] = $info['width'];
			$post['thumb_height'] = $info['height'];
		} else {
			list($thumb_w, $thumb_h) = $thumb_size;
			if (!createThumbnail($file_location, $thumb_location, $thumb_w, $thumb_h)) {
				@unlink($file_location);
				make_error("Could not create thumbnail.");
			}

			$post['thumb_width'] = $thumb_w;
			$post['thumb_height'] = $thumb_h;
		}

		$post['image_width'] = $info['width'];
		$post['image_height'] = $info['height'];
	}

	if ($post['file'] == '') { // No file uploaded
		if (!$post['parent']) {
			make_error("An image is required to start a thread.");
		}
		if (str_replace('<br>', '', $post['message']) == "") {
			make_error("Please enter a message and/or upload an image to make a reply.");
		}
	} else {
		echo $post['file_original'] . ' uploaded.<br>';
	}

	$post['id'] = insertPost($post);

	trimThreads();

	if ($post['parent']) {
		rebuildThread($post['parent']);

		if (strtolower($post['email']) != 'sage')
			bumpThreadByID($post['parent']);
	} else {
		rebuildThread($post['id']);
	}

	rebuildIndexes();
	if (strtolower($post['email']) == 'noko')
		$dest = 'res/'.(!$post['parent'] ? $post['id'] : $post['parent']).'.html#'.$post['id'];
	else
		$dest = 'index.html';

	redirect(expand_path($dest));
// Check if the request is to delete a post and/or its associated image
} elseif (isset($_GET['delete']) && !isset($_GET['manage'])) {
	if (!isset($_POST['delete']))
		make_error('Tick the box next to a post and click "Delete" to delete it.');

	$post = postByID($_POST['delete']);
	if (!$post)
		make_error('Sorry, an invalid post identifier was sent.  Please go back, refresh the page, and try again.');

	list($loggedin, $isadmin) = manageCheckLogIn();

	if ($loggedin && $_POST['password'] == '') {
		// Redirect to post moderation page
		redirect(get_script_name().'?manage&moderate='.$post['id']);
	} elseif ($post['password'] != '' && md5(md5($_POST['password'])) == $post['password']) {
		deletePostByID($post['id']);
		if (!$post['parent']) {
			threadUpdated($post['id']);
		} else {
			threadUpdated($post['parent']);
		}
		make_error('Post deleted.');
	} else {
		make_error('Invalid password.');
	}
// Check if the request is to access the management area
} elseif (isset($_GET['manage'])) {
	$text = '';
	$onload = '';
	$navbar = '&nbsp;';
	$loggedin = false;
	$isadmin = false;
	$returnlink = basename($_SERVER['PHP_SELF']);

	list($loggedin, $isadmin) = manageCheckLogIn();

	if ($loggedin) {
		if ($isadmin) {
			if (isset($_GET['rebuildall'])) {
				$allthreads = allThreads();
				foreach ($allthreads as $thread) {
					rebuildThread($thread['id']);
				}
				rebuildIndexes();
				$text .= manageInfo('Rebuilt board.');
			} elseif (isset($_GET['bans'])) {
				clearExpiredBans();

				if (isset($_POST['ip'])) {
					if ($_POST['ip'] != '') {
						$banexists = banByIP($_POST['ip']);
						if ($banexists) {
							make_error('Sorry, there is already a ban on record for that IP address.');
						}

						$ban = array();
						$ban['ip'] = $_POST['ip'];
						$ban['expire'] = ($_POST['expire'] > 0) ? (time() + $_POST['expire']) : 0;
						$ban['reason'] = $_POST['reason'];

						insertBan($ban);
						$text .= manageInfo('Ban record added for ' . $ban['ip']);
					}
				} elseif (isset($_GET['lift'])) {
					$ban = banByID($_GET['lift']);
					if ($ban) {
						deleteBanByID($_GET['lift']);
						$text .= manageInfo('Ban record lifted for ' . $ban['ip']);
					}
				}

				$onload = manageOnLoad('bans');
				$text .= manageBanForm();
				$text .= manageBansTable();
			}
		}

		if (isset($_GET['delete'])) {
			$post = postByID($_GET['delete']);
			if ($post) {
				deletePostByID($post['id']);
				rebuildIndexes();
				if ($post['parent']) {
					rebuildThread($post['parent']);
				}
				$text .= manageInfo('Post No.' . $post['id'] . ' deleted.');
			} else {
				make_error("Sorry, there doesn't appear to be a post with that ID.");
			}
		} elseif (isset($_GET['moderate'])) {
			if ($_GET['moderate'] > 0) {
				$post = postByID($_GET['moderate']);
				if ($post) {
					$text .= manageModeratePost($post);
				} else {
					make_error("Sorry, there doesn't appear to be a post with that ID.");
				}
			} else {
				$onload = manageOnLoad('moderate');
				$text .= manageModeratePostForm();
			}
		} elseif (isset($_GET["rawpost"])) {
			$onload = manageOnLoad("rawpost");
			$text .= manageRawPostForm();
		} elseif (isset($_GET["logout"])) {
			session_destroy();
			redirect(get_script_name().'?manage');
		}
		if ($text == '') {
			$text = manageStatus();
		}
	} else {
		$onload = manageOnLoad('login');
		$text .= manageLogInForm();
	}

	echo managePage($text, $onload);
} else {
	if (!file_exists('index.html'))
		rebuildIndexes();

	redirect(expand_path('index.html'));
}
