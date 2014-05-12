<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

function expand_path($filename, $internal = false) {
	global $app; // TODO

	if ($internal) {
		return get_script_name().
			$app['path']->create("/$filename", $internal);
	}

	$dirname = dirname(get_script_name());

	// avoid double slashes
	if ($dirname === '/')
		return "/$filename";

	return "$dirname/$filename";
}

function expand_script_path($script, $dest, $vars = array()) {
	$dirname = dirname(get_script_name());

	// avoid double slashes
	if ($dirname === '/')
		$dirname = '';

	$url = "$dirname/$script?/$dest";

	foreach ($vars as $key => $value)
		$url .= '&'.urlencode($key).'='.urlencode($value);

	return $url;
}

function expand_api_path($path, $vars) {
	return expand_script_path('ajax.php', $path, $vars);
}

function get_script_name() {
	global $app;

	return $app['request']->server['SCRIPT_NAME'];
}


/*
 * this is like some sort of reverse parse_url() for the current request.
 * of course, it all depends on a correct server setup, which might not be the
 * case with a sloppy reverse proxy setups.
 */
function get_url($path = false) {
	global $app;
	static $url;

	if (isset($url)) {
		return $url;
	}

	$param = $app['param']->flags('server strict');

	if ($path === false) {
		$path = $param->get('REQUEST_URI');
	}

	$https = $param->get('HTTPS');
	$user = $param->get('PHP_AUTH_USER');
	$pass = $param->get('PHP_AUTH_PW');
	$host = $param->get('HTTP_HOST')
		?: $param->get('SERVER_NAME')
		?: 'localhost';
	$port = $param->get('SERVER_PORT');

	$url = 'http';

	// https
	if ($https) {
		$url .= 's';
	}

	$url .= '://';

	// authentication
	if ($user !== false && $user !== '') {
		$url .= $user;

		if ($pass !== false && $pass !== '')
			$url .= ':'.$pass;

		$url .= '@';
	}

	// hostname, might include port number
	$url .= $host;

	// port number
	// SERVER_NAME might include one, so we have to check the host variable
	if (!preg_match('/:\d+$/', $host)
	&& ($https && $port != 443 || !$https && $port != 80)) {
		$url .= ":$port";
	}

	// path
	$url .= $path;

	return $url;
}

function redirect($url) {
	header(sprintf('Location: %s', $url), true, 303);

	echo '<html><body><a href="'.$url.'">'.$url.'</a></body></html>';
}

/**
 * Minifies and combines the JavaScript files specified in the configuration
 * into one file and returns the path to it.
 *
 * @return string Path to combined JavaScript file
 */
function get_js() {
	global $app;

	static $static_cache;

	// load from static var cache
	if (isset($web_path))
		return $web_path;

	// try loading from persistent cache
	$data = $app['cache']->get('js_cache');

	if ($data !== false)
		return $data;

	// output path
	$path = 'static/js/cache-'.time().'.js';

	// start suppressing output - jsmin+ is dumb and echoes errors instead
	// of throwing exceptions
	ob_start();

	$fh = fopen(TINYIB_ROOT."/$path", 'w');

	if (!$fh) {
		ob_end_clean();
		throw new Exception("Cannot write to /static/js/.");
	}

	foreach ($app['js.includes'] as $filename) {
		if (strpos($filename, '/') === false)
			$filename = TINYIB_ROOT.'/static/js/'.$filename;

		$js = file_get_contents($filename);

		try {
			$temp = JSMinPlus::minify($js);
		} catch (Exception $e) {
			continue;
		}

		// concatenate to the output file
		fwrite($fh, "$temp;");
	}

	fclose($fh);
	ob_end_clean();

	$web_path = expand_path($path);

	$app['cache']->set('js_cache', $web_path);

	return $web_path;
}


//
// Flood stuff
//

function make_comment_hex($str) {
	// remove cross-board citations
	// the numbers don't matter
	$str = preg_replace('!>>>/[A-Za-z0-9]+/!', '', $str);

	if (function_exists('iconv')) {
		// remove diacritics and other noise
		// FIXME: this removes cyrillic entirely
		$str = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $str);
	}

	$str = strtolower($str);

	// strip all non-alphabet characters
	$str = preg_replace('/[^a-z]/', '', $str);

	if ($str === '')
		return '';

	return sha1($str);
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

function make_name_tripcode($input, $tripkey = '!') {
	global $app;

	$tripcode = '';

	// Check if we can reencode strings
	static $has_encode;
	if (!isset($has_encode)) 
		$has_encode = extension_loaded('mbstring');

	// Split name into chunks
	$bits = explode('#', $input, 3);
	list($name, $trip, $secure) = array_pad($bits, 3, false);

	// Anonymous?
	if (!is_string($name) || !length($name))
		$name = false;

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
		$hash = sha1($secure.$app['secret']);
		$hash = substr(base64_encode($hash), 0, 10);
		$tripcode .= $tripkey.$tripkey.$hash;
	}

	return array($name, $tripcode);
}

function writePage($filename, $contents) {
	global $app;

	$tempfile = tempnam($app['path.tmp'], 'tmp'); /* Create the temporary file */
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

function create_ban_message($post) {
	// comment goes at the top
	$msg = "\n\n";

	if ($post->md5)
		$msg .= 'MD5: '.$post->md5."\n";

	$msg .= 'Name: ';
	$msg .= html_entity_decode($post->name, ENT_QUOTES, 'UTF-8');
	$msg .= "\n";

	if ($post->tripcode)
		$reason .= ' '.strip_tags($post->tripcode);

	$comment = html_entity_decode($post->comment, ENT_QUOTES, 'UTF-8');
	$comment = strip_tags($comment);

	$msg .= "Comment:\n$comment";

	return $msg;
}

/**
 * Despite the name, this does not log us in - it merely check if we're logged
 * in. If we are, it returns an instance of User. If we aren't, it redirects us
 * or returns false.
 *
 * Use this in page functions to check the login.
 *
 * @deprecated
 */
function do_login($url = false) {
	try {
		$user = get_session_login();
	} catch (UserException $e) {
		$user = false;
	}

	if ($user !== false) {
		return $user;
	}

	if ($url !== false) {
		diverge('/login', array('goto' => urlencode($url)));
		exit;
	}

	return false;
}

/**
 * @deprecated
 */
function get_session_login() {
	global $app;

	$session = $app['session'];

	if (isset($session['login']) && $session['login'] !== false) {
		return unserialize($session['login']);
	}

	return false;
}

/**
 * @deprecated
 */
function redirect_after_login($goto = false) {
	if ($goto) {
		$goto = urldecode($goto);

		diverge($goto);
		return;
	}

	diverge("/manage");
}
