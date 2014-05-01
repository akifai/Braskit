<?php
/*
 * This file performs actions which are common for every valid entry point.
 * Every entry point should define its own exception handler so errors being
 * thrown out become sane.
 *
 * Copyright (C) 2013 plainboards.org
 * Based on TinyIB, copyright (C) 2009(?)-2012 Trevor Slocum.
 *
 * See LICENSE for terms and conditions of use.
 */

if (PHP_SAPI === 'cli' && !defined('TINYIB'))
	define('TINYIB', null);

defined('TINYIB') or exit;

ignore_user_abort(true);

// sessions
if (!defined('TINYIB_NO_SESSIONS') || TINYIB_NO_SESSIONS) {
	session_name('TINYIB');
	session_start();
}

date_default_timezone_set('Europe/Berlin');

// some constants
define('TINYIB_ROOT', realpath(dirname(__FILE__).'/..'));

// Load classes automagically
require(TINYIB_ROOT.'/inc/class.autoload.php');
AutoLoader::register();

// Misc functions
require(TINYIB_ROOT.'/inc/functions.php');

// Exception handlers rely on the above include
if (defined('TINYIB_EXCEPTION_HANDLER'))
	set_exception_handler(TINYIB_EXCEPTION_HANDLER);

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

	set_magic_quotes_runtime(false);
}

$app = new App();

$app['request'] = function () {
	return new Request();
};

$app['param'] = $app->factory(function () use ($app) {
	return new Param($app['request']);
});

// Constants for debug
define('DEBUG_NONE', 0);
define('DEBUG_JS', 4);
define('DEBUG_LESS', 8);
define('DEBUG_CACHE', 16);
define('DEBUG_ALL', ~0);

$debug = false;

// Cache - TODO
$app['cache'] = function () {
	global $debug;

	if ($debug & DEBUG_CACHE) {
		return new Cache_Debug();
	}

	if (ini_get('apc.enabled') && extension_loaded('apc')) {
		return new Cache_APC();
	}
	
	return new Cache_PHP();
};

// setting for enabling debug features
$app['template.debug'] = false;

// returns a filesystem loader for inc/templates
$app['template.loader'] = function () {
	return new PlainIB_Twig_Loader(TINYIB_ROOT.'/inc/templates');
};

// returns a new chain loader
$app['template.chain'] = $app->factory(function () {
	return new Twig_Loader_Chain();
});

$app['template.creator'] = $app->protect(function ($loader) use ($app) {
	global $cache_dir;

	$twig = new Twig_Environment($loader, array(
		'cache' => $app['template.debug'] ? false : $cache_dir,
		'debug' => $app['template.debug'],
	));

	$twig->addExtension(new PlainIB_Twig_Extension());

	// Load debugger
	if ($app['template.debug']) {
		$twig->addExtension(new Twig_Extension_Debug());
	}

	return $twig;
});

$app['template'] = function () use ($app) {
	return $app['template.creator']($app['template.loader']);
};

if (defined('TINYIB_INSTALLER') && TINYIB_INSTALLER) {
	// temporary config variables needed for the installer
	$temp_dir = sys_get_temp_dir();
	$cache_dir = $temp_dir.'/plainib-cache';

	// we can't use a config or database for this entry point
	return;
}

if (file_exists(TINYIB_ROOT.'/config.php')) {
	// Load the config
	require(TINYIB_ROOT.'/config.php');
} else {
	// no config == not installed
	redirect('install.php');
	exit;
}

if ($debug === 1 || $debug === true)
	$debug = DEBUG_ALL;

// establish database connection
$app['dbh'] = function () use ($db_name, $db_host, $db_username, $db_password) {
	return new DBConnection($db_name, $db_host, $db_username, $db_password);
};

$app['db'] = function () use ($app, $db_prefix) {
	return new Database($app['dbh'], $db_prefix);
};

// Site configuration
$app['config'] = function () {
	return new GlobalConfig();
};
