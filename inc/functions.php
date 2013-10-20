<?php
defined('TINYIB') or exit;

function get_routes() {
	require(TINYIB_ROOT.'/inc/routes.php');

	return $routes;
}

//
// Caching
//

// TODO
define('TINYIB_CACHE', ini_get('apc.enabled') ? 'apc' : 'php');

function get_cache($key) {
	global $cache_dir, $debug;

	// debug mode - don't get cache
	if ($debug & DEBUG_CACHE)
		return false;

	// APC
	if (TINYIB_CACHE === 'apc')
		return apc_fetch($key);

	// Plain PHP cache
	if (TINYIB_CACHE === 'php') {
		$filename = "$cache_dir/cache-{$key}.php";

		@include($filename);

		// we couldn't load the cache
		if (!isset($cache) || $expired) {
			@unlink($filename);
			return false;
		}

		return $cache;
	}

	// unknown cache type
	return false;
}

function set_cache($key, $data, $ttl = 0) {
	global $cache_dir, $debug;

	// debug mode - don't save cache
	if ($debug & DEBUG_CACHE)
		return true;

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

		writePage("$cache_dir/cache-$key.php", $content);
		return true;
	}

	// unknown cache type
	return false;
}

function delete_cache($key) {
	global $cache_dir, $debug;

	// debug mode - don't delete cache
	if ($debug & DEBUG_CACHE)
		return true;

	// APC
	if (TINYIB_CACHE === 'apc')
		return apc_delete($key);

	// Plain PHP cache
	if (TINYIB_CACHE === 'php')
		return @unlink("$cache_dir/cache-$key.php");

	// it's gone
	return true;
}

function empty_cache() {
	global $cache_dir;

	// APC
	if (TINYIB_CACHE === 'apc')
		return apc_clear_cache('user');

	// Plain PHP cache
	if (TINYIB_CACHE === 'php') {
		// get list of cache files
		$files = glob("$cache_dir/cache-*.php");

		// that didn't work for some reason
		if (!is_array($files))
			return false;

		foreach ($files as $file)
			unlink($file);
	}

	// unknown cache type
	return false;
}



// Simple formatting
function format_post($comment, $default_comment, $cb, $raw = false) {
	$comment = preg_replace('/\r?\n|\r/', "\n", $comment);
	$comment = trim($comment);

	// set default comment
	if ($comment === '') {
		$comment = $default_comment;
		$raw = true;
	}

	// raw HTML - nothing to do
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

function expand_path($filename, $internal = false) {
	global $request_handler;

	if ($internal) {
		return get_script_name().
			$request_handler::create("/$filename", $internal);
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

function get_script_name() {
	return $_SERVER['SCRIPT_NAME'];
}


/*
 * this is like some sort of reverse parse_url() for the current request.
 * of course, it all depends on a correct server setup, which might not be the
 * case with a sloppy reverse proxy setups.
 */
function get_url($path = false) {
	static $url;
	if (isset($url))
		return $url;

	$https = getenv('HTTPS');
	$user = getenv('PHP_AUTH_USER');
	$pass = getenv('PHP_AUTH_PW');
	$host = (getenv('HTTP_HOST') ?: getenv('SERVER_NAME')) ?: 'localhost';
	$port = getenv('SERVER_PORT');
	if ($path === false)
		$path = getenv('REQUEST_URI');

	$url = 'http';

	// https
	if ($https)
		$url .= 's';

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
	&& ($https && $port != 443 || !$https && $port != 80))
		$url .= ":$port";

	// path
	$url .= $path;

	return $url;
}

/// Internal redirect
function diverge($dest, $args = array()) {
	global $request_handler;

	// missing slash
	if (substr($dest, 0, 1) !== '/')
		$dest = "/$goto";

	redirect($request_handler::create($dest, $args));
}

function redirect($url) {
	header(sprintf('Location: %s', $url), true, 303);

	echo '<html><body><a href="'.$url.'">'.$url.'</a></body></html>';
}

function load_twig(array $dirs = array()) {
	global $cache_dir;
	global $debug;

	$dirs[] = 'inc/templates';

	$loader = new PlainIB_Twig_Loader($dirs);

	$twig = new Twig_Environment($loader, array(
		'cache' => ($debug & DEBUG_TEMPLATE) ? false : $cache_dir,
		'debug' => (bool)($debug & DEBUG_TEMPLATE),
	));

	$twig->addExtension(new PlainIB_Twig_Extension());

	// Load debugger
	if ($debug & DEBUG_TEMPLATE)
		$twig->addExtension(new Twig_Extension_Debug());

	return $twig;
}

function render($template, $args = array(), $twig = null) {
	if ($twig === null) {
		global $twig;

		// Load Twig if necessary
		if (!isset($twig))
			$twig = load_twig();
	}

	$output = $twig->render($template, $args);

	return $output;
}

/**
 * Minifies and combines the JavaScript files specified in the configuration
 * into one file and returns the path to it.
 *
 * @return string Path to combined JavaScript file
 */
function get_js() {
	global $javascript_includes;
	static $static_cache;

	// load from static var cache
	if (isset($web_path))
		return $web_path;

	// try loading from persistent cache
	$cache = get_cache('js_cache');

	if ($cache !== false)
		return $cache;

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

	foreach ($javascript_includes as $filename) {
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

	set_cache('js_cache', $web_path);

	return $web_path;
}


//
// Parameter fetching
//

define('PARAM_STRING', 1); // can be string
define('PARAM_ARRAY', 2); // can be array
define('PARAM_GET', 4); // can be GET value
define('PARAM_POST', 8); // can be POST value
define('PARAM_COOKIE', 16); // can be cookie value
define('PARAM_STRICT', 32); // returns false if parameter is missing
define('PARAM_DEFAULT', PARAM_STRING | PARAM_GET | PARAM_POST);

function param($name, $flags = PARAM_DEFAULT) {
	if (!$flags) {
		// no flags
		throw new LogicException('param() expects type & method flags');
	}

	if (!($flags & (PARAM_STRING | PARAM_ARRAY))) {
		// missing type flag(s)
		throw new LogicException('param() expects type flags');
	}

	if (!($flags & (PARAM_GET | PARAM_POST | PARAM_COOKIE))) {
		// missing method flag(s)
		throw new LogicException('param() expects method flags');
	}

	if ($flags & PARAM_STRICT)
		$default = false;
	elseif ($flags & PARAM_STRING)
		$default = '';
	elseif ($flags & PARAM_ARRAY)
		$default = array();

	if (($flags & PARAM_POST) && isset($_POST[$name])) {
		// POST values
		$value = $_POST[$name];
	} elseif (($flags & PARAM_GET) && isset($_GET[$name])) {
		// GET values
		$value = $_GET[$name];
	} elseif (($flags & PARAM_COOKIE) && isset($_COOKIE[$name])) {
		// COOKIE values
		$value = $_COOKIE[$name];
	} else {
		// no parameter found
		return $default;
	}

	// return defined string
	if (($flags & PARAM_STRING) && is_string($value))
		return $value;

	// return defined array
	if (($flags & PARAM_ARRAY) && is_array($value))
		return $value;

	// type mismatch
	return $default;
}


//
// CSRF
//

function do_csrf($url = false) {
	// Only POST requests can validate our CSRF token
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		if (check_csrf())
			return; // success

		throw new Exception('Invalid CSRF token.');
	}

	// this is a GET request - display a confirmation
	echo render('csrf.html', array(
		'display_url' => $url ?: $_SERVER['REQUEST_URI'],
		'token' => get_csrf_token(),
		'url' => $_SERVER['REQUEST_URI'],
	));

	exit;
}

function check_csrf() {
	$sent = param('csrf', PARAM_STRING | PARAM_POST);

	if ($sent === get_csrf_token()) {
		unset_csrf_token();
		return true; // success
	}

	return false;
}

function get_csrf_token() {
	if (isset($_SESSION['csrf_token']))
		return $_SESSION['csrf_token'];

	// no token set
	return $_SESSION['csrf_token'] = random_string(48);
}

function unset_csrf_token() {
	unset($_SESSION['csrf_token']);
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
	global $secret;

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
		$hash = sha1($secure.$secret);
		$hash = substr(base64_encode($hash), 0, 10);
		$tripcode .= $tripkey.$tripkey.$hash;
	}

	return array($name, $tripcode);
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

function get_login_credentials() {
	return array(
		param('login_user', PARAM_DEFAULT & ~PARAM_GET),
		param('login_pass', PARAM_DEFAULT & ~PARAM_GET),
	);
}

/**
 * Despite the name, this does not log us in - it merely check if we're logged
 * in. If we are, it returns an instance of User. If we aren't, it redirects us
 * or returns false.
 *
 * Use this in page functions to check the login.
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

function get_session_login() {
	if (isset($_SESSION['login']) && $_SESSION['login'] !== false)
		return unserialize($_SESSION['login']);

	return false;
}

function redirect_after_login($goto = false) {
	if ($goto) {
		$goto = urldecode($goto);

		diverge($goto);
		return;
	}

	diverge("/manage");
}
