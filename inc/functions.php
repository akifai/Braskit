<?php
defined('TINYIB_BOARD') or exit;

//
// Script utilities
//

function load_page($tasks) {
	if (isset($_GET['task']))
		$task = &$_GET['task'];
	elseif (isset($_POST['task']))
		$task = &$_POST['task'];

	if (isset($task) && in_array($task, $tasks, true)) {
		if ($_SERVER['REQUEST_METHOD'] === 'POST') {
			do_task_function($task, 'post');
		} else {
			do_task_function($task, 'get');
		}
	} elseif (!isset($task)) {
		redirect(expand_path('index.html'));
	} else {
		header('Status: 404 Not Found', true);
		make_error('Invalid task.');
	}
}

function do_task_function($page, $method) {
	// Load the file
	$filename = 'inc/task.'.$page.'.php';

	if (!file_exists($filename))
		make_error("Couldn't load page '$page'.", true, true);

	require $filename;

	$funcname = sprintf('%s_%s', $page, $method);
	if (function_exists($funcname)) {
		call_user_func($funcname);
	} else {
		header('Status: 405 Method Not Allowed', true);
		make_error('Method not allowed.');
	}
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



//
// Rebuild stuff
//

function rebuildIndexes() {
	$threads = get_index_threads();
	$pagecount = floor(count($threads) / 10);

	$num = 0;

	$page = array_splice($threads, 0, 10);
	do {
		$file = !$num ? 'index.html' : $num.'.html';
		$html = render('page.html', array(
			'threads' => $page,
			'pagenum' => $num,
			'pagecount' => $pagecount,
		));

		writePage($file, $html);
		$num++;
	} while ($page = array_splice($threads, 0, 10));
}

function rebuildThread($id) {
	$posts = postsInThreadByID($id);
	$html = render('thread.html', array(
		'posts' => $posts,
		'thread' => $id,
	));

	writePage(sprintf('res/%d.html', $id), $html);
}

// Threads/indexes/stuff
function get_index_threads() {
	$all_threads = allThreads(); 
	$threads = array();

	foreach ($all_threads as $thread) {
		$thread = array($thread);
		$replies = latestRepliesInThreadByID($thread[0]['id']);

		foreach ($replies as $reply)
			$thread[] = $reply;

		$thread[0]['omitted'] = (count($replies) == 3)
			? (count(postsInThreadByID($thread[0]['id'])) - 4)
			: 0;

		$threads[] = $thread;
	}

	return $threads;
}








//
// Unsorted
//

function cleanString($str) {
	return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function plural($singular, $count, $plural = 's') {
	if ($plural == 's') {
		$plural = $singular . $plural;
	}
	return ($count == 1 ? $singular : $plural);
}

function expand_path($filename) {
	return dirname(get_script_name()).'/'.$filename;
}

function get_script_name() {
	return $_SERVER['SCRIPT_NAME'];
}

function redirect($url) {
	header(sprintf('Location: %s', $url), true, 303);

	echo '<html><body><a href="'.$url.'">'.$url.'</a></body></html>';
}

function render($template, $args = array()) {
	global $twig;

	// Load Twig if necessary
	if (!isset($twig)) {
		require_once 'inc/lib/Twig/Autoloader.php';
		Twig_Autoloader::register();

		$loader = new Twig_Loader_Filesystem('inc/templates/');
		$twig = new Twig_Environment($loader, array(
			'cache' => !TINYIB_DEBUG,
			'debug' => TINYIB_DEBUG,
		));

		// Load debugging stuff
		if (TINYIB_DEBUG) {
			$twig->addExtension(new Twig_Extension_Debug());
		}

		// Globals
		$twig->addFunction('self', new Twig_Function_Function('get_script_name'));
		$twig->addFunction('path', new Twig_Function_Function('expand_path'));
	}

	try {
		return $twig->render($template, $args);
	} catch (Twig_Error $e) {
		make_error($e->getRawMessage(), true, $e->getTrace());
	}
}

function threadUpdated($id) {
	rebuildThread($id);
	rebuildIndexes();
}

function newPost($parent = 0) {
	return array('parent' => $parent,
				'timestamp' => '0',
				'bumped' => '0',
				'ip' => '',
				'name' => '',
				'tripcode' => '',
				'email' => '',
				'date' => '',
				'subject' => '',
				'message' => '',
				'password' => '',
				'file' => '',
				'file_hex' => '',
				'file_original' => '',
				'file_size' => '0',
				'file_size_formatted' => '',
				'image_width' => '0',
				'image_height' => '0',
				'thumb' => '',
				'thumb_width' => '0',
				'thumb_height' => '0');
}

function convertBytes($number) {
	$len = strlen($number);
	if ($len < 4) {
		return sprintf("%dB", $number);
	} elseif ($len <= 6) {
		return sprintf("%0.2fKB", $number/1024);
	} elseif ($len <= 9) {
		return sprintf("%0.2fMB", $number/1024/1024);
	}

	return sprintf("%0.2fGB", $number/1024/1024/1024);
}

function make_name_tripcode($input, $tripkey = '!') {
	$tripcode = '';

	// Check if we can reencode strings
	static $has_encode;
	if (!isset($has_encode)) 
		$has_encode = extension_loaded('mb_string');

	// Split name into chunks
	$bits = explode('#', $input, 3);
	list($name, $trip, $secure) = array_pad($bits, 3, false);

	// Anonymous?
	if ($name === false || preg_match('/^\s*$/', $name))
		$name = 'Anonymous';

	// Do regular tripcodes
	if ($trip !== false && (strlen($trip) !== 0 || $secure === false)) {
		if ($has_encode)
			$trip = mb_convert_encoding($trip, 'UTF-8', 'SJIS');

		$salt = substr($trip.'H..', 1, 2);
		$salt = preg_replace('/[^\.-z]/', '.', $salt);
		$salt = strtr($salt, ':;<=>?@[\\]^_`', 'ABCDEFGabcdef');

		$tripcode = $tripkey.substr(crypt($trip, $salt), -10);
	}

	// Do secure tripcodes
	if ($secure !== false) {
		$hash = sha1($secure.TINYIB_TRIPSEED);
		$hash = substr(base64_encode($hash), 0, 10);
		$tripcode .= $tripkey.$tripkey.$hash;
	}

	return array($name, $tripcode);
}

function make_date($timestamp) {
	return date('y/m/d(D)H:i:s', $timestamp);
}

function writePage($filename, $contents) {
	$tempfile = tempnam('res/', TINYIB_BOARD . 'tmp'); /* Create the temporary file */
	$fp = fopen($tempfile, 'w');
	fwrite($fp, $contents);
	fclose($fp);
	/* If we aren't able to use the rename function, try the alternate method */
	if (!@rename($tempfile, $filename)) {
		copy($tempfile, $filename);
		unlink($tempfile);
	}

	chmod($filename, 0664); /* it was created 0600 */
}

function _postLink($matches) {
	$post = postByID($matches[1]);
	if ($post) {
		return '<a href="res/' . (!$post['parent'] ? $post['id'] : $post['parent']) . '.html#' . $matches[1] . '">' . $matches[0] . '</a>';
	}
	return $matches[0];
}

function postLink($message) {
	return preg_replace_callback('/&gt;&gt;([0-9]+)/', '_postLink', $message);
}

function colorQuote($message) {
	if (substr($message, -1, 1) != "\n") { $message .= "\n"; }
	return preg_replace('/^(&gt;[^\>](.*))\n/m', '<span class="unkfunc">\\1</span>' . "\n", $message);
}

function deletePostImages($post) {
	if ($post['file'] != '') { @unlink('src/' . $post['file']); }
	if ($post['thumb'] != '') { @unlink('thumb/' . $post['thumb']); }
}

function checkBanned() {
	$ban = banByIP($_SERVER['REMOTE_ADDR']);
	if ($ban) {
		if ($ban['expire'] == 0 || $ban['expire'] > time()) {
			$expire = ($ban['expire'] > 0) ? ('<br>This ban will expire ' . date('y/m/d(D)H:i:s', $ban['expire'])) : '<br>This ban is permanent and will not expire.';
			$reason = ($ban['reason'] == '') ? '' : ('<br>Reason: ' . $ban['reason']);
			make_error('Your IP address ' . $ban['ip'] . ' has been banned from posting on this image board.  ' . $expire . $reason);
		} else {
			clearExpiredBans();
		}
	}
}

function checkFlood() {
	if (TINYIB_DELAY > 0) {
		$lastpost = lastPostByIP();
		if ($lastpost) {
			if ((time() - $lastpost['timestamp']) < TINYIB_DELAY) {
				make_error("Please wait a moment before posting again.  You will be able to make another post in " . (TINYIB_DELAY - (time() - $lastpost['timestamp'])) . " " . plural("second", (TINYIB_DELAY - (time() - $lastpost['timestamp']))) . ".");
			}
		}
	}
}

function checkMessageSize() {
	if (strlen($_POST["message"]) > 8000) {
		make_error("Please shorten your message, or post it in multiple parts. Your message is " . strlen($_POST["message"]) . " characters long, and the maximum allowed is 8000.");
	}
}

function manageCheckLogIn() {
	$loggedin = false; $isadmin = false;
	if (isset($_POST['password'])) {
		if ($_POST['password'] == TINYIB_ADMINPASS) {
			$_SESSION['tinyib'] = TINYIB_ADMINPASS;
		} elseif (TINYIB_MODPASS != '' && $_POST['password'] == TINYIB_MODPASS) {
			$_SESSION['tinyib'] = TINYIB_MODPASS;
		}
	}

	if (isset($_SESSION['tinyib'])) {
		if ($_SESSION['tinyib'] == TINYIB_ADMINPASS) {
			$loggedin = true;
			$isadmin = true;
		} elseif (TINYIB_MODPASS != '' && $_SESSION['tinyib'] == TINYIB_MODPASS) {
			$loggedin = true;
		}
	}

	return array($loggedin, $isadmin);
}

function setParent() {
	if (isset($_POST["parent"])) {
		if ($_POST["parent"]) {
			if (!threadExistsByID($_POST['parent'])) {
				make_error("Invalid parent thread ID supplied, unable to create post.");
			}

			return $_POST["parent"];
		}
	}

	return 0;
}

function isRawPost() {
	if (isset($_POST['rawpost'])) {
		list($loggedin, $isadmin) = manageCheckLogIn();
		if ($loggedin) {
			return true;
		}
	}

	return false;
}

function validateFileUpload() {
	switch ($_FILES['file']['error']) {
		case UPLOAD_ERR_OK:
			break;
		case UPLOAD_ERR_FORM_SIZE:
			make_error("That file is larger than " . TINYIB_MAXKBDESC . ".");
			break;
		case UPLOAD_ERR_INI_SIZE:
			make_error("The uploaded file exceeds the upload_max_filesize directive (" . ini_get('upload_max_filesize') . ") in php.ini.");
			break;
		case UPLOAD_ERR_PARTIAL:
			make_error("The uploaded file was only partially uploaded.");
			break;
		case UPLOAD_ERR_NO_FILE:
			make_error("No file was uploaded.");
			break;
		case UPLOAD_ERR_NO_TMP_DIR:
			make_error("Missing a temporary folder.");
			break;
		case UPLOAD_ERR_CANT_WRITE:
			make_error("Failed to write file to disk");
			break;
		default:
			make_error("Unable to save the uploaded file.");
	}
}

function checkDuplicateImage($hex) {
	$hexmatches = postsByHex($hex);
	if (count($hexmatches) > 0) {
		foreach ($hexmatches as $hexmatch) {
			make_error("Duplicate file uploaded. That file has already been posted <a href=\"res/" . ((!$hexmatch["parent"]) ? $hexmatch["id"] : $hexmatch["parent"]) . ".html#" . $hexmatch["id"] . "\">here</a>.");
		}
	}
}

function createThumbnail($name, $filename, $new_w, $new_h) {
	$system = explode(".", $filename);
	$system = array_reverse($system);
	if (preg_match("/jpg|jpeg/", $system[0])) {
		$src_img = imagecreatefromjpeg($name);
	} else if (preg_match("/png/", $system[0])) {
		$src_img = imagecreatefrompng($name);
	} else if (preg_match("/gif/", $system[0])) {
		$src_img = imagecreatefromgif($name);
	} else {
		return false;
	}

	if (!$src_img) {
		make_error("Unable to read uploaded file during thumbnailing. A common cause for this is an incorrect extension when the file is actually of a different type.");
	}
	$old_x = imageSX($src_img);
	$old_y = imageSY($src_img);
	$percent = ($old_x > $old_y) ? ($new_w / $old_x) : ($new_h / $old_y);
	$thumb_w = round($old_x * $percent);
	$thumb_h = round($old_y * $percent);

	$dst_img = ImageCreateTrueColor($thumb_w, $thumb_h);
	fastImageCopyResampled($dst_img, $src_img, 0, 0, 0, 0, $thumb_w, $thumb_h, $old_x, $old_y);

	if (preg_match("/png/", $system[0])) {
		if (!imagepng($dst_img, $filename)) {
			return false;
		}
	} else if (preg_match("/jpg|jpeg/", $system[0])) {
		if (!imagejpeg($dst_img, $filename, 70)) {
			return false;
		}
	} else if (preg_match("/gif/", $system[0])) {
		if (!imagegif($dst_img, $filename)) { 
			return false;
		}
	}

	imagedestroy($dst_img); 
	imagedestroy($src_img); 

	return true;
}

function fastImageCopyResampled(&$dst_image, &$src_image, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h, $quality = 3) {
	// Author: Tim Eckel - Date: 12/17/04 - Project: FreeRingers.net - Freely distributable. 
	if (empty($src_image) || empty($dst_image)) { return false; }

	if ($quality <= 1) {
		$temp = imagecreatetruecolor ($dst_w + 1, $dst_h + 1);

		imagecopyresized ($temp, $src_image, $dst_x, $dst_y, $src_x, $src_y, $dst_w + 1, $dst_h + 1, $src_w, $src_h);
		imagecopyresized ($dst_image, $temp, 0, 0, 0, 0, $dst_w, $dst_h, $dst_w, $dst_h);
		imagedestroy ($temp);
	} elseif ($quality < 5 && (($dst_w * $quality) < $src_w || ($dst_h * $quality) < $src_h)) {
		$tmp_w = $dst_w * $quality;
		$tmp_h = $dst_h * $quality;
		$temp = imagecreatetruecolor ($tmp_w + 1, $tmp_h + 1);

		imagecopyresized ($temp, $src_image, $dst_x * $quality, $dst_y * $quality, $src_x, $src_y, $tmp_w + 1, $tmp_h + 1, $src_w, $src_h);
		imagecopyresampled ($dst_image, $temp, 0, 0, 0, 0, $dst_w, $dst_h, $tmp_w, $tmp_h);
		imagedestroy ($temp);
	} else {
		imagecopyresampled ($dst_image, $src_image, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h);
	}

	return true;
}

function strallpos($haystack, $needle, $offset = 0) {
	$result = array();
	for ($i = $offset;$i<strlen($haystack);$i++) {
		$pos = strpos($haystack, $needle, $i);
		if ($pos !== False) {
			$offset = $pos;
			if ($offset >= $i) {
				$i = $offset;
				$result[] = $offset;
			}
		}
	}
	return $result;
}
