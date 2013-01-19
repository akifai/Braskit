<?php
/*
 * Copyright (C) 2013 shanachan.org
 * Based on TinyIB, copyright (C) 2009(?)-2012 Trevor Slocum.
 *
 * See LICENSE for terms and conditions of use.
 */

ini_set('display_errors', true);
error_reporting(E_ALL);
session_start();

require 'settings.php';
require 'inc/database.php';
require 'inc/functions.php';

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

if (!file_exists('settings.php'))
	make_error('Please rename the file settings.default.php to settings.php');

if (!TINYIB_TRIPSEED || !TINYIB_ADMINPASS)
	make_error('TINYIB_TRIPSEED and TINYIB_ADMINPASS must be configured');

// Check directories are writable by the script
foreach (array('.', 'res', 'src', 'thumb') as $dir)
	if (!is_writable($dir))
		make_error("Directory '$dir/' can not be written to. Please modify its permissions.");

if (!file_exists('index.html'))
	rebuildIndexes();

// Array of valid tasks
$tasks = array(
	'post', 'delete',
	'manage',
	'bans', 'addban', 'liftban',
	'rebuild',
	'login', 'logout',
);

load_page($tasks);
