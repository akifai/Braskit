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

$board_re = '([A-Za-z0-9]+)';
$num_re = '([1-9]\d{0,9})';

$tasks = array(
	// User actions
	"/$board_re/post" => 'post',
	"/$board_re/delete" => 'delete',

	// Mod view
	"/$board_re/(?:$num_re(?:\\.html)?|index\\.html)?" => 'viewpage',
	"/$board_re/res/$num_re(?:\\.html)?" => 'viewthread',

	// Mod board actions
	"/$board_re/rebuild" => 'rebuild',

	// Mod global actions
	'/manage' => 'manage',
	'/bans' => 'bans',
	'/add_ban' => 'addban',
	"/lift_ban(?:/$num_re)?" => 'liftban',
	"/edit_ban/$num_re" => 'editban',
	"/view_IP/([a-f0-9.:/]+)" => 'viewip',
	'/login' => 'login',
	'/logout' => 'logout',
);

unset($board_re, $num_re);

$loader = new TaskLoader($tasks, 'pages');
$loader->run();

// print buffer
ob_end_flush();
