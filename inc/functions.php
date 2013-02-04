<?php
defined('TINYIB') or exit;

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

function ob_callback($buffer) {
	global $start_time;

	// We don't want to modify non-html responses
	if (!in_array('Content-Type: text/html; charset=UTF-8', headers_list()))
		return $buffer;

	// the part of the buffer before the footer closes
	$ins = strrpos($buffer, "</p>\n</body>");
	if ($ins === false)
		return $buffer;

	// first part of the new buffer
	$newbuf = substr($buffer, 0, $ins); 

	$total_time = microtime(true) - $start_time;
	$query_time = round(100 / $total_time * TinyIB_DB::$time);

	// Append debug text
	$newbuf .= sprintf('<br>Page generated in %0.4f seconds,'.
	' of which %d%% was spent running %d database queries.',
		$total_time, $query_time, TinyIB_DB::$queries);

	// the rest of the buffer
	$newbuf .= substr($buffer, $ins);

	return $newbuf;
}



//
// Caching
//

function get_cache($key) {
	// debug mode - don't get cache
	if (TINYIB_DEBUG)
		return false;

	// APC
	if (TINYIB_CACHE === 'apc')
		return apc_fetch($key);

	// Plain PHP cache
	if (TINYIB_CACHE === 'php') {
		$filename = sprintf('cache/cache-%s.php', $key);
		include $filename;

		// we couldn't load the cache
		if (!isset($cache))
			return false;

		// the cache expired, remove it
		if ($expired) {
			unlink($filename);
			return false;
		}

		return $cache;
	}

	// unknown cache type
	return false;
}

function set_cache($key, $data, $ttl = 0) {
	// debug mode - don't save cache
	if (TINYIB_DEBUG)
		return false;

	// APC
	if (TINYIB_CACHE === 'apc')
		return apc_add($key, $data, $ttl);

	// Plain PHP cache
	if (TINYIB_CACHE === 'php') {
		@mkdir('cache'); // FIXME

		// Content of the cache file
		$content = '<?php ';

		if ($ttl) {
			$eol = time() + $ttl; // end of life for cache
			$content .= sprintf('$expired = time() > %d;', $eol);
		} else {
			// the cache never expires
			$content .= '$expired = false;';
		}

		$dumped_data = var_export($data, true);
		$content .= sprintf('$cache = %s;', $dumped_data);

		writePage(sprintf('cache/cache-%s.php', $key), $content);
		return true;
	}

	// unknown cache type
	return false;
}

function empty_cache() {
	// APC
	if (TINYIB_CACHE === 'apc')
		return apc_clear_cache('user');

	// Plain PHP cache
	if (TINYIB_CACHE === 'php') {
		// get list of cache files
		$files = glob('cache/cache-*.php');

		// that didn't work for some reason
		if (!is_array($files))
			return false;

		foreach ($files as $file)
			unlink($file);
	}

	// unknown cache type
	return false;
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
function get_index_threads($offset = false) {
	if ($offset !== false)
		$all_threads = getThreads($offset);
	else
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
	$dirname = dirname(get_script_name());

	// avoid double slashes
	if ($dirname === '/')
		return "/$filename";

	return "$dirname/$filename";
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
		$twig->addFunction('filename', new Twig_Function_Function('shorten_filename'));
	}

	try {
		return $twig->render($template, $args);
	} catch (Twig_Error $e) {
		make_error($e->getRawMessage(), true, $e->getTrace());
	}
}




//
// Parameter fetching
//

define('PARAM_STRING', 1); // can be string
define('PARAM_ARRAY', 2); // can be array
define('PARAM_GET', 4); // can be GET value
define('PARAM_POST', 8); // can be POST value
define('PARAM_COOKIE', 16); // can be cookie value
define('PARAM_STRICT', 32); // must be defined
define('PARAM_DEFAULT', PARAM_STRING | PARAM_GET | PARAM_POST);

function param($name, $flags = PARAM_DEFAULT /* string | get | post */) {
	// no flags - nothing to do
	if (!$flags)
		return false;

	$value = false;

	if (($flags & PARAM_POST) && isset($_POST[$name])) {
		// POST values
		$value = $_POST[$name];
	} elseif (($flags & PARAM_GET) && isset($_GET[$name])) {
		// GET values
		$value = $_GET[$name];
	} elseif (($flags & PARAM_COOKIE) && isset($_COOKIE[$name])) {
		// COOKIE values
		$value = $_COOKIE[$name];
	}

	// return empty value in non-strict mode
	if (!($flags & PARAM_STRICT) && $value === false) {
		// Empty string
		if ($flags & PARAM_STRING)
			return '';

		// Empty array
		if ($flags & PARAM_ARRAY)
			return array();
	}

	// return defined string
	if (($flags & PARAM_STRING) && is_string($value))
		return $value;

	// return defined array
	if (($flags & PARAM_ARRAY) && is_array($value))
		return $value;

	return false;
}


//
// Unsorted
//

function random_string($length = 8,
$pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ') {
    // Number of possible outcomes
    $outcomes = is_array($pool) ? count($pool) : strlen($pool);
	$outcomes--;

	$str = '';
	while ($length--)
		$str .= $pool[mt_rand(0, $outcomes)];

	return $str;
}

function length($str) {
	// Don't remove trailing spaces - wakabamark/markdown uses them for
	// block code formatting
	$str = rtrim($str);

	if (extension_loaded('mbstring'))
		return mb_strlen($str, 'UTF-8');

	return strlen($str);
}

function make_size($size, $base2 = false) {
	if (!$size)
		return '0 B';

	if ($base2) {
		$n = 1024;
		$s = array('B', 'KiB', 'MiB', 'GiB', 'TiB');
	} else {
		$n = 1000;
		$s = array('B', 'kB', 'MB', 'GB', 'TB');
	}

	for ($i = 0; $i <= 3; $i++) {
		if ($size >= pow($n, $i) && $size < pow($n, $i + 1)) {
			$unit = $s[$i];
			$number = round($size / pow($n, $i), 2);
			return sprintf('%s %s', $number, $unit);
		}
	}

	$unit = $s[4];
	$number = round($size / pow($n, 4), 2);
	return sprintf('%s %s', $number, $unit);
}

function shorten_filename($filename) {
	$info = pathinfo($filename);

	// short enough
	if (length($info['basename']) <= 25)
		return $filename;

	// cut basename while preserving UTF-8 if possible
	$short = !ctype_digit($info['basename']) && extension_loaded('mbstring')
		? mb_substr($info['basename'], 0, 25, 'UTF-8')
		: substr($info['basename'], 0, 25);

	return sprintf('%s(...).%s', $short, $info['extension']);
}

function handle_upload($name) {
	if (!isset($_FILES[$name]) || $_FILES[$name]['name'] === '')
		return false; // no file uploaded - nothing to do

	// Check for file[] or variable tampering through register_globals
	if (is_array($_FILES[$name]['name']))
		make_error('Abnormal post.');

	// Check for uploading errors
	validateFileUpload($_FILES[$name]);

	extract($_FILES[$name], EXTR_REFS);

	// load image functions
	require 'inc/image.php';

	// Check file size
	if ((TINYIB_MAXKB > 0) && ($size > (TINYIB_MAXKB * 1024)))
		make_error(sprintf('That file is larger than %s.', TINYIB_MAXKBDESC));

	// set some values
	$file['tmp'] = $tmp_name;
	$file['md5'] = md5_file($tmp_name);
	$file['size'] = $size;
	$file['origname'] = $name;

	// check for duplicate upload
	checkDuplicateImage($file['md5']);

	// generate a number to use as our filename
	$basename = time().substr(microtime(), 2, 3);

	$info = analyse_image($tmp_name);

	if ($info === false)
		make_error('Only GIF, JPG, and PNG files are allowed.'); 

	$file['width'] = $info['width'];
	$file['height'] = $info['height'];

	// filename for main file
	$file['file'] = sprintf('%s.%s', $basename, $info['ext']);

	// filename for thumbnail
	$file['thumb'] = sprintf('%ss.%s', $basename, $info['ext']);

	// paths
	$file['location'] = 'src/'.$file['file'];
	$file['t_location'] = 'thumb/'.$file['thumb'];

	// make thumbnail sizes
	$t_size = make_thumb_size(
		$info['width'],
		$info['height'],
		TINYIB_MAXW,
		TINYIB_MAXH
	);

	if ($t_size === false) {
		// TODO: It may be desirable to thumbnail the image even if it's
		// small enough already.
		$file['t_tmp'] = true;

		$file['t_width'] = $info['width'];
		$file['t_height'] = $info['height'];
	} else {
		list($t_width, $t_height) = $t_size;

		// create a temporary name for thumbnail
		$file['t_tmp'] = tempnam(sys_get_temp_dir(), 'tinyib');

		// create thumbnail
		$created = createThumbnail($tmp_name, $file['t_tmp'],
			$info['ext'], $info['width'], $info['height'],
			$t_width, $t_height);

		if ($created) {
			// success
			$file['t_width'] = $t_width;
			$file['t_height'] = $t_height;
		} else {
			// we couldn't create the thumbnail for whatever reason
			// 0x0 indicates failure
			$file['t_width'] = 0;
			$file['t_height'] = 0;

			// indicate that we shouldn't bother with this further
			$file['t_tmp'] = false;
		}
	}

	return $file;
}

function newPost($parent = 0) {
	return array(
		'parent' => $parent,
		'timestamp' => 0,
		'bumped' => 0,
		'ip' => '',
		'name' => '',
		'tripcode' => '',
		'email' => '',
		'date' => '',
		'subject' => '',
		'comment' => '',
		'password' => '',
		'file' => '',
		'md5' => '',
		'origname' => '',
		'size' => 0,
		'prettysize' => '',
		'width' => 0,
		'height' => 0,
		'thumb' => '',
		't_width' => 0,
		't_height' => 0,
	);
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

function check_login() {
	// Check session
	if (isset($_SESSION['tinyib']) && $_SESSION['tinyib'])
		return true;

	// We're logging in
	if (isset($_POST['password']) && $_POST['password'] === TINYIB_ADMINPASS) {
		$_SESSION['tinyib'] = true;
		return true;
	}

	return false;
}

function validateFileUpload($file) {
	$msg = false;

	// Detect tampering through register_globals
	if (!isset($file['error']) || !isset($file['tmp_name']))
		make_error('Abnormal post.');

	switch ($file['error']) {
	case UPLOAD_ERR_OK:
		// The upload is seemingly okay - now let's be sure the file
		// actually did originate from an upload and not through
		// tampering with register_globals
		if (!is_uploaded_file($file['tmp_name']))
			make_error('Abnormal post.');

		// We're done.
		return;
	case UPLOAD_ERR_FORM_SIZE:
		$msg = sprintf('That file is larger than %s.', TINYIB_MAXKBDESC);
		break;
	case UPLOAD_ERR_INI_SIZE:
		$msg = 'The file is too large.';
		break;
	case UPLOAD_ERR_PARTIAL:
		$msg = 'The file was only partially uploaded.';
		break;
	case UPLOAD_ERR_NO_FILE:
		$msg = 'No file was uploaded.';
		break;
	case UPLOAD_ERR_NO_TMP_DIR:
		$msg = 'Missing a temporary folder.';
		break;
	case UPLOAD_ERR_CANT_WRITE:
		$msg = 'Failed to write file to disk.';
		break;
	default:
		$msg = 'Unable to save the uploaded file.';
	}

	make_error('Error: '.$msg);
}

function checkDuplicateImage($hex) {
	$row = postByHex($hex);
	if ($row === false)
		return;

	make_error('Duplicate file uploaded.');

	// TODO: doesn't work because HTML is escaped
	#make_error("Duplicate file uploaded. That file has already been posted
	#<a href=\"res/" . ((!$hexmatch["parent"]) ? $hexmatch["id"] : $hexmatch["parent"])
	#. ".html#" . $hexmatch["id"] . "\">here</a>.");
}

function createThumbnail($src, $dst, $ext, $width, $height, $t_width, $t_height) {
	if ($ext === 'jpg')
		$ext = 'jpeg';

	$src_img = call_user_func('imagecreatefrom'.$ext, $src);
	$dst_img = ImageCreateTrueColor($t_width, $t_height);

	fastImageCopyResampled($dst_img, $src_img, 0, 0, 0, 0,
		$t_width, $t_height, $width, $height);

	$success = false;

	if ($ext === 'jpeg')
		$success = imagejpeg($dst_img, $dst, 70);
	elseif ($ext === 'png')
		$success = imagepng($dst_img, $dst);
	elseif ($ext === 'gif')
		$success = imagegif($dst_img, $dst);

	imagedestroy($dst_img);
	imagedestroy($src_img);

	return $success;
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
