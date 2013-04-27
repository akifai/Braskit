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

defined('TINYIB') or exit;

// sessions
if (!defined('TINYIB_NO_SESSIONS') || TINYIB_NO_SESSIONS) {
	session_name('TINYIB');
	session_start();
}

// some constants
define('TINYIB_ROOT', realpath(dirname(__FILE__).'/..'));

// Load classes automagically
require(TINYIB_ROOT.'/inc/autoload.php');

// Database code
require(TINYIB_ROOT.'/inc/database.php');

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

if (defined('TINYIB_INSTALLER') && TINYIB_INSTALLER) {
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

// Don't connect to database or load config from database
if (defined('TINYIB_NO_DATABASE') && TINYIB_NO_DATABASE)
	return;

// Load DB-specific query functions
$db_code = TINYIB_ROOT."/inc/schema/{$db_driver}.php";

if (file_exists($db_code)) {
	require($db_code);
	unset($db_code);
} else {
	throw new Exception("Unknown database type: '$db_driver'.");
}

// Connect to database
$dbh = new Database($db_driver, $db_name, $db_host, $db_username, $db_password);

// Global configuration
$config = new Config();
