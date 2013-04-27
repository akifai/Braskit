<?php

/*
 * Compiles LESS files and serves them cached.
 * TODO: Relative paths in url()
 */

define('TINYIB', null);

// never show errors - use the error log instead
ini_set('display_errors', false);
error_reporting(-1);

header('Content-Type: text/css');

// global init
define('TINYIB_EXCEPTION_HANDLER', 'less_exception_handler');
define('TINYIB_NO_DATABASE', true);
define('TINYIB_NO_SESSIONS', true);
require('inc/global_init.php');

if (isset($_GET['file']) && is_string($_GET['file']))
	$source = $_GET['file'];
else
	throw new Exception('No source file specified.');

// check filename
if (!preg_match('/^[A-Za-z0-9._-]+\.less$/', $source))
	throw new Exception('Invalid source filename.');

$filename = TINYIB_ROOT.'/static/less/'.$source;

// check if .less file exists
if (!file_exists($filename))
	throw new Exception('Source file does not exist.');

// get file modification time
// should we clearstatcache() first, perhaps?
$mtime = filemtime($filename);

// try loading the compiled CSS from cache
$cache_key = 'less_'.md5($source);
$cache = get_cache($cache_key);

// we need to rebuild
if (!$cache || $cache['mtime'] !== $mtime) {
	$cache = array('mtime' => $mtime);

	// compile the less
	$less = new lessc_fixed;

	if (!$debug)
		$less->setFormatter('compressed');

	$cache['content'] = $less->compileFile($filename);

	// store in cache
	set_cache($cache_key, $cache);
}

if (!$debug) {
	header('Expires: '.gmdate('D, d M Y H:i:s \G\M\T', time() + 1209600));
	header('Cache-Control: max-age=1209600');

	// Remove Pragma: no-cache
	if (function_exists('header_remove'))
		header_remove('Pragma');
}

echo $cache['content'];


//
// Exception handler
//

function less_exception_handler($e) {
	// so it doesn't get cached
	header('HTTP/1.1 404 Not Found');

	echo '/* '.$e->getMessage().' */';
}
