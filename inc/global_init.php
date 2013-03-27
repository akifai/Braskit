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

// sesisons
session_name('TINYIB');
session_start();

// some constants
define('TINYIB_ROOT', realpath(dirname(__FILE__).'/..'));

// Copy default settings file if needed.
if (!file_exists(TINYIB_ROOT.'/settings.php'))
	copy(TINYIB_ROOT.'/settings.default.php', TINYIB_ROOT.'/settings.php');

// Load settings. The default settings will throw an exception, forcing the
// administrator to edit the file.
require(TINYIB_ROOT.'/settings.php');

// Load classes automagically
require(TINYIB_ROOT.'/inc/autoload.php');

// Database code
require(TINYIB_ROOT.'/inc/database.php');

// Misc functions
require(TINYIB_ROOT.'/inc/functions.php');

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

if (!TINYIB_TRIPSEED || !TINYIB_ADMINPASS)
	throw new Exception('TINYIB_TRIPSEED and TINYIB_ADMINPASS must be configured');
