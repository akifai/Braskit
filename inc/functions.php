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
		throw new Exception('Invalid task.');
	}
}

function do_task_function($page, $method) {
	// Load the file
	$filename = 'inc/task.'.$page.'.php';

	if (!file_exists($filename))
		throw new Exception("Couldn't load page '$page'.", true, true);

	require $filename;

	$funcname = sprintf('%s_%s', $page, $method);
	if (function_exists($funcname)) {
		call_user_func($funcname);
	} else {
		header('Status: 405 Method Not Allowed', true);
		throw new Exception('Method not allowed.');
	}
}

function make_error_page($e) {
	$referrer = @$_SERVER['HTTP_REFERER'];

	try {
		// Error messages using Twig
		echo render('error.html', array(
			'message' => $e->getMessage(),
			'referrer' => $referrer,
		));
	} catch (Exception $yolo) {

	}

	exit;
}

function ob_callback($buffer) {
	global $start_time;

	// We don't want to modify non-html responses
	if (!in_array('Content-Type: text/html; charset=UTF-8', headers_list()))
		return $buffer;

	// the part of the buffer before the footer closes
	$ins = strrpos($buffer, "</p></body>");
	if ($ins === false)
		return $buffer;

	// first part of the new buffer
	$newbuf = substr($buffer, 0, $ins); 

	$total_time = microtime(true) - $start_time;
	$query_time = round(100 / $total_time * Database::$time);

	// Append debug text
	$newbuf .= sprintf('<br>Page generated in %0.4f seconds,'.
	' of which %d%% was spent running %d database queries.',
		$total_time, $query_time, Database::$queries);

	// the rest of the buffer
	$newbuf .= substr($buffer, $ins);

	return $newbuf;
}



//
// Caching
//

function get_cache($key) {
	global $debug;

	// debug mode - don't get cache
	if ($debug)
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
	global $debug;

	// debug mode - don't save cache
	if ($debug)
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

function format_post($comment, Callable $cb, $raw = false) {
	// Simple formatting

	$comment = preg_replace('/\r?\n|\r/', "\n", $comment);
	$comment = trim($comment);

	if ($raw)
		return $comment;

	do {
		// remove excessive newlines
		$comment = str_replace("\n\n\n", "\n\n", $comment, $count);
	} while ($count);
	
	$lines = explode("\n", $comment);

	foreach ($lines as &$line) {
		$line = trim($line);
		$line = cleanString($line);

		// do >>1 references
		$line = preg_replace_callback('/&gt;&gt;(\d+)/', $cb, $line);

		// "greentexting"
		if (strpos($line, '&gt;') === 0)
			$line = '<span class="unkfunc">'.$line.'</span>';
	}

	$comment = implode("<br>", $lines);

	return $comment;
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

function load_twig() {
	global $debug;

	$loader = new TinyIB_Twig_Loader('inc/templates/');
	$twig = new Twig_Environment($loader, array(
		'cache' => $debug ? false : 'cache/',
		'debug' => $debug,
	));

	// Load debugging stuff
	if ($debug) {
		$twig->addExtension(new Twig_Extension_Debug());
	}

	// Globals
	$functions = array(
		'self' => 'get_script_name',
		'path' => 'expand_path',
		'filename' => 'shorten_filename',
	);

	foreach ($functions as $name => $value)
		$twig->addFunction($name, new Twig_Function_Function($value));

	return $twig;
}

function render($template, $args = array()) {
	global $twig;

	// Load Twig if necessary
	if (!isset($twig))
		$twig = load_twig();

	try {
		$output = $twig->render($template, $args);
	} catch (Twig_Error $e) {
		// lol
		echo "<plaintext>$e";
		die();
	}

	return $output;
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
		$has_encode = extension_loaded('mbstring');

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
	global $temp_dir;

	$tempfile = tempnam($temp_dir, 'tmp'); /* Create the temporary file */
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

function deletePostImages($board, $post) {
	$files = array();

	if ($post['file'])
		$files[] = "{$board}/src/{$post['file']}";
	if ($post['thumb'])
		$files[] = "{$board}/thumb/{$post['thumb']}";
	if (!$post['parent'])
		$files[] = "{$board}/res/{$post['id']}.html";

	foreach ($files as $file)
		@unlink($file);
}

function checkBanned() {
	$ban = banByIP($_SERVER['REMOTE_ADDR']);
	if ($ban) {
		if ($ban['expire'] == 0 || $ban['expire'] > time()) {
			$expire = ($ban['expire'] > 0) ? ('<br>This ban will expire ' . date('y/m/d(D)H:i:s', $ban['expire'])) : '<br>This ban is permanent and will not expire.';
			$reason = ($ban['reason'] == '') ? '' : ('<br>Reason: ' . $ban['reason']);
			throw new Exception('Your IP address ' . $ban['ip'] . ' has been banned from posting on this image board.  ' . $expire . $reason);
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
				throw new Exception("Please wait a moment before posting again.  You will be able to make another post in " . (TINYIB_DELAY - (time() - $lastpost['timestamp'])) . " " . plural("second", (TINYIB_DELAY - (time() - $lastpost['timestamp']))) . ".");
			}
		}
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
		throw new Exception('Abnormal post.');

	switch ($file['error']) {
	case UPLOAD_ERR_OK:
		// The upload is seemingly okay - now let's be sure the file
		// actually did originate from an upload and not through
		// tampering with register_globals
		if (!is_uploaded_file($file['tmp_name']))
			throw new Exception('Abnormal post.');

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

	throw new Exception('Error: '.$msg);
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
