<?php
/*
 * Copyright (C) 2013 plainboards.org
 * Based on TinyIB, copyright (C) 2009(?)-2012 Trevor Slocum.
 *
 * See LICENSE for terms and conditions of use.
 */

$start_time = microtime(true);

// this is a valid entry point
define('TINYIB', null);

// Default exception handler for web shit
define('TINYIB_EXCEPTION_HANDLER', 'make_error_page');

// start sessions, load config, escape magic quotes, etc
require('inc/global_init.php');

// force utf-8
header('Content-Type: text/html; charset=UTF-8', true);

// start buffering
ob_start('ob_callback');

// Array of valid tasks
$tasks = array(
	'/post' => 'post',
	'/delete' => 'delete',

	'/addban' => 'addban',
	'/bans' => 'bans',
	'/liftban' => 'liftban',
	'/delete' => 'delete',
	'/login' => 'login',
	'/logout' => 'logout',
	'/manage' => 'manage', // deprecated
	'/rebuild' => 'rebuild', // deprecated

	# /b/ | /b/index.html | /b/1 | /b/1.html
	#'/([A-Za-z0-9]+)/(?:(?:index\.html)?|/([1-9]\d{0,9})(?:\.html)?)'
	#	=> 'viewpage',

	# /b/res/1 | /b/res/1.html
	#'/([A-Za-z0-9]+)/res/([1-9]\d{0,9})(?:\.html)?' => 'viewthread',

	# /b/rebuild
	#'/([A-Za-z0-9]+)/rebuild' => 'rebuild',
);

// Temp. fix for legacy URLs
if (isset($_SERVER['QUERY_STRING'])
&& substr($_SERVER['QUERY_STRING'], 0, 5) === 'task='
&& strlen($_SERVER['QUERY_STRING']) > 5)
	$_SERVER['QUERY_STRING'] = '/'.substr($_SERVER['QUERY_STRING'], 5);

$loader = new TaskLoader($tasks, 'pages');
$loader->run();

// print buffer
ob_end_flush();
