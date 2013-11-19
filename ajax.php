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
define('TINYIB_BASE_TEMPLATE', 'ajax_base.html');

require('./inc/global_init.php');

header('Content-Type: application/json; charset=UTF-8', true);

$ajax = array('error' => false);

ob_start('ob_ajax_callback');

$path = new Path_QueryString();
$router = new Router_Main($path->get());

$view = new $router->view($router);
echo $view->responseBody;

// print buffer
ob_end_flush();


//
// functions specific to ajax.php
//

function diverge($dest, $args = array()) {
	global $ajax;

	$ajax['diverge'] = $dest;
	$ajax['divergeArgs'] = $args;
}

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
	global $ajax;

	$vars = array('page' => $output);

	foreach ($ajax as $key => $value)
		$vars[$key] = $value;

	return json_encode($vars);
}
