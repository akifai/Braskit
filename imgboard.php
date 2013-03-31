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
