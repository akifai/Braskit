<?php
/*
 * Copyright (C) 2013 plainboards.org
 * Based on TinyIB, copyright (C) 2009(?)-2012 Trevor Slocum.
 *
 * See LICENSE for terms and conditions of use.
 */

define('TINYIB', null);

// never let PHP print errors - this fucks up the JSON
ini_set('display_errors', 0);

define('TINYIB_EXCEPTION_HANDLER', 'ajax_exception_handler');

require('inc/global_init.php');

header('Content-Type: application/json; charset=UTF-8', true);

ob_start('ob_ajax_callback');

$board_re = '([A-Za-z0-9]+)';
$num_re = '([1-9]\d{0,8})';

$tasks = array(
	"/$board_re/ban" => 'ban',
);

unset($board_re, $num_re);

$loader = new RouteQueryString($tasks, 'pages', true);
$loader->run();

// print buffer
ob_end_flush();


//
// functions specific to ajax.php
//

function ajax_exception_handler($e) {
	ob_end_clean();

	header('HTTP/1.1 403 Forbidden');

	echo json_encode(array(
		'error' => true,
		'errorMsg' => $e->getMessage(),
	));

	exit;
}

function ob_ajax_callback($output) {
	return json_encode(array('page' => $output));
}
