<?php
# TinyIB
#
# https://github.com/tslocum/TinyIB

error_reporting(E_ALL);
ini_set("display_errors", 1);
session_start();
ob_implicit_flush();
ob_end_flush();

if (get_magic_quotes_gpc()) {
	foreach ($_GET as $key => $val) { $_GET[$key] = stripslashes($val); }
	foreach ($_POST as $key => $val) { $_POST[$key] = stripslashes($val); }
}
if (get_magic_quotes_runtime()) { set_magic_quotes_runtime(0); }

function fancyDie($message) {
	die('<body text="#800000" bgcolor="#FFFFEE" align="center"><br><div style="display: inline-block; background-color: #F0E0D6;font-size: 1.25em;font-family: Tahoma, Geneva, sans-serif;padding: 7px;border: 1px solid #D9BFB7;border-left: none;border-top: none;">' . $message . '</div><br><br>- <a href="javascript:history.go(-1)">Click here to go back</a> -</body>');
}

if (!file_exists('settings.php')) {
	fancyDie('Please rename the file settings.default.php to settings.php');
}
require 'settings.php';
require 'inc/database.php';
require 'inc/functions.php';
require 'inc/html.php';

if (TINYIB_TRIPSEED == '' || TINYIB_ADMINPASS == '') {
	fancyDie('TINYIB_TRIPSEED and TINYIB_ADMINPASS must be configured');
}

// Check directories are writable by the script
foreach (array('res', 'src', 'thumb') as $dir) {
	if (!is_writable($dir)) {
		fancyDie("Directory '" . $dir . "' can not be written to.  Please modify its permissions.");
	}
}

$redirect = true;
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

	$post['name'] = cleanString(substr($post['name'], 0, 75));
	$post['email'] = cleanString(str_replace('"', '&quot;', substr($_POST['field2'], 0, 75)));
	$post['subject'] = cleanString(substr($_POST['field3'], 0, 75));
	if ($rawpost) {
		$rawposttext = ($isadmin) ? ' <span style="color: red;">## Admin</span>' : ' <span style="color: purple;">## Mod</span>';
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
			fancyDie("File transfer failure. Please retry the submission.");

		if ((TINYIB_MAXKB > 0) && (filesize($tmp_name) > (TINYIB_MAXKB * 1024)))
			fancyDie("That file is larger than " . TINYIB_MAXKBDESC . ".");

		if (($info = analyse_image($tmp_name)) === false)
			fancyDie("Failed to read the size of the uploaded file. Please retry the submission.");

		$post['file_original'] = $_FILES['file']['name'];
		$post['file_hex'] = md5_file($tmp_name);
		$post['file_size'] = $_FILES['file']['size'];
		$post['file_size_formatted'] = convertBytes($post['file_size']);
		$file_name = time().substr(microtime(), 2, 3);
		$post['file'] = sprintf('%s.%s', $file_name, $info['ext']);
		$post['thumb'] = sprintf('%ss.%s', $file_name, $info['ext']);

		if (!in_array($info['ext'], array('jpg', 'gif', 'png')))
			fancyDie("Only GIF, JPG, and PNG files are allowed.");

		$file_location = "src/" . $post['file'];
		$thumb_location = "thumb/" . $post['thumb'];

		checkDuplicateImage($post['file_hex']);

		if (!move_uploaded_file($tmp_name, $file_location))
			fancyDie("Could not copy uploaded file.");

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
				fancyDie("Could not create thumbnail.");
			}

			$post['thumb_width'] = $thumb_w;
			$post['thumb_height'] = $thumb_h;
		}

		$post['image_width'] = $info['width'];
		$post['image_height'] = $info['height'];
	}

	if ($post['file'] == '') { // No file uploaded
		if (!$post['parent']) {
			fancyDie("An image is required to start a thread.");
		}
		if (str_replace('<br>', '', $post['message']) == "") {
			fancyDie("Please enter a message and/or upload an image to make a reply.");
		}
	} else {
		echo $post['file_original'] . ' uploaded.<br>';
	}

	$post['id'] = insertPost($post);
	if (strtolower($post['email']) == 'noko') {
		$redirect = 'res/' . ($post['parent'] == !$post['parent'] ? $post['id'] : $post['parent']) . '.html#' . $post['id'];
	}

	trimThreads();

	echo 'Updating thread...<br>';
	if ($post['parent']) {
		rebuildThread($post['parent']);

		if (strtolower($post['email']) != 'sage') {
			bumpThreadByID($post['parent']);
		}
	} else {
		rebuildThread($post['id']);
	}

	echo 'Updating index...<br>';
	rebuildIndexes();
// Check if the request is to delete a post and/or its associated image
} elseif (isset($_GET['delete']) && !isset($_GET['manage'])) {
	if (!isset($_POST['delete'])) { fancyDie('Tick the box next to a post and click "Delete" to delete it.'); }

	$post = postByID($_POST['delete']);
	if ($post) {
		list($loggedin, $isadmin) = manageCheckLogIn();

		if ($loggedin && $_POST['password'] == '') {
			// Redirect to post moderation page
			echo '--&gt; --&gt; --&gt;<meta http-equiv="refresh" content="0;url=' . basename($_SERVER['PHP_SELF']) . '?manage&moderate=' . $_POST['delete'] . '">';
		} elseif ($post['password'] != '' && md5(md5($_POST['password'])) == $post['password']) {
			deletePostByID($post['id']);
			if (!$post['parent']) { threadUpdated($post['id']); } else { threadUpdated($post['parent']); }
			fancyDie('Post deleted.');
		} else {
			fancyDie('Invalid password.');
		}
	} else {
		fancyDie('Sorry, an invalid post identifier was sent.  Please go back, refresh the page, and try again.');
	}

	$redirect = false;
// Check if the request is to access the management area
} elseif (isset($_GET['manage'])) {
	$text = ''; $onload = ''; $navbar = '&nbsp;';
	$redirect = false; $loggedin = false; $isadmin = false;
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
							fancyDie('Sorry, there is already a ban on record for that IP address.');
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
				fancyDie("Sorry, there doesn't appear to be a post with that ID.");
			}
		} elseif (isset($_GET['moderate'])) {
			if ($_GET['moderate'] > 0) {
				$post = postByID($_GET['moderate']);
				if ($post) {
					$text .= manageModeratePost($post);
				} else {
					fancyDie("Sorry, there doesn't appear to be a post with that ID.");
				}
			} else {
				$onload = manageOnLoad('moderate');
				$text .= manageModeratePostForm();
			}
		} elseif (isset($_GET["rawpost"])) {
			$onload = manageOnLoad("rawpost");
			$text .= manageRawPostForm();
		} elseif (isset($_GET["logout"])) {
			$_SESSION['tinyib'] = '';
			session_destroy();
			die('--&gt; --&gt; --&gt;<meta http-equiv="refresh" content="0;url=' . $returnlink . '?manage">');
		}
		if ($text == '') {
			$text = manageStatus();
		}
	} else {
		$onload = manageOnLoad('login');
		$text .= manageLogInForm();
	}

	echo managePage($text, $onload);
} elseif (!file_exists('index.html') || countThreads() == 0) {
	rebuildIndexes();
}

if ($redirect) {
	echo '--&gt; --&gt; --&gt;<meta http-equiv="refresh" content="0;url=' . (is_string($redirect) ? $redirect : 'index.html') . '">';
}

?>
