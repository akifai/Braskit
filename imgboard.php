<?php
/*
 * Copyright (C) 2013 plainboards.org
 * Based on TinyIB, copyright (C) 2009(?)-2012 Trevor Slocum.
 *
 * See LICENSE for terms and conditions of use.
 */

$start_time = microtime(true);

ini_set('display_errors', true);
error_reporting(E_ALL);
session_start();

define('TINYIB', true);

// Must be loaded prior to anything else
require 'inc/functions.php';

// Default exception handler for web shit
set_exception_handler('make_error_page');

// Copy default settings file if needed
if (!file_exists('settings.php'))
	copy('settings.default.php', 'settings.php');

require 'settings.php';
require 'inc/class.board.php';
require 'inc/database.php';

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
}

// force utf-8
header('Content-Type: text/html; charset=UTF-8', true);

// start buffering
ob_start('ob_callback');

if (!TINYIB_TRIPSEED || !TINYIB_ADMINPASS)
	throw new Exception('TINYIB_TRIPSEED and TINYIB_ADMINPASS must be configured');

// Array of valid tasks
$tasks = array(
	// user actions
	'post', 'delete',
	// mod actions
	'manage', 'login', 'logout',
	// - bans
	'bans', 'addban', 'liftban',
	// - other
	'rebuild',
);

load_page($tasks);

// print buffer
ob_end_flush();
