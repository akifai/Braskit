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

$request = new Request();

// Constants for debug
define('DEBUG_NONE', 0);
define('DEBUG_TEMPLATE', 2);
define('DEBUG_JS', 4);
define('DEBUG_LESS', 8);
define('DEBUG_CACHE', 16);
define('DEBUG_ALL', ~0);

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

// Don't connect to database or load config from database
if (defined('TINYIB_NO_DATABASE') && TINYIB_NO_DATABASE)
	return;

// establish database connection
$dbh = new DBConnection($db_name, $db_host, $db_username, $db_password);
$db = new Database($dbh, $db_prefix);

// Site configuration
$config = new GlobalConfig();
