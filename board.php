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

$app['path'] = function () use ($app) {
	return new Path_QueryString($app['request']);
};

$app['router'] = function () use ($app) {
	return new Router_Main($app['path']->get());
};

$view = new $app['router']->view($app['router']);
echo $view->responseBody;

// print buffer
ob_end_flush();


//
// functions specific to board.php
//

/// Internal redirect
function diverge($dest, $args = array()) {
	global $app; // TODO

	// missing slash
	if (substr($dest, 0, 1) !== '/')
		$dest = "/$goto";

	redirect($app['path']->create($dest, $args));
}

function make_error_page($e) {
	$flags = PARAM_STRING | PARAM_SERVER | PARAM_STRICT;
	$referrer = param('HTTP_REFERER', $flags);

	$message = $e->getMessage();

	$template = 'error.html';

	if (!($e instanceof HTMLException)) {
		// escape HTML
		$message = Parser::escape($message);
	} elseif ($e instanceof BanException) {
		// show the ban screen
		$template = 'banned.html';
	}

	try {
		// Error messages using Twig
		echo render($template, array(
			'exception' => $e,
			'message' => $message,
			'referrer' => $referrer,
		));
	} catch (Exception $e) {
		header('Content-Type: text/plain; charset=UTF-8');
		echo "[PlainIB] Fatal exception/template error.\n\n";
		echo $e->getMessage();
	}

	exit();
}

function ob_callback($buffer) {
	global $app, $start_time;

	// We don't want to modify non-html responses
	if (!in_array('Content-Type: text/html; charset=UTF-8', headers_list()))
		return $buffer;

	// the part of the buffer to insert before
	$ins = strrpos($buffer, "<!--footer_insert-->");
	if ($ins === false)
		return $buffer;

	// first part of the new buffer
	$newbuf = substr($buffer, 0, $ins);

	$total_time = microtime(true) - $start_time;
	$query_time = round(100 / $total_time * $app['dbh']->time);

	// Append debug text
	$newbuf .= sprintf('<br>Page generated in %0.4f seconds,'.
	' of which %d%% was spent running %d database queries.',
		$total_time, $query_time, $app['dbh']->queries);

	// the rest of the buffer
	$newbuf .= substr($buffer, $ins);

	return $newbuf;
}
