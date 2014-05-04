<?php

define('TINYIB', null);

// don't load the config or database
define('TINYIB_INSTALLER', true);

// let us download the session-stored config without having session cookies set
ini_set('session.use_only_cookies', false);

require('inc/global_init.php');

if (
	file_exists(TINYIB_ROOT.'/config.php') &&
	!isset($app['session']['installer'])
) {
	header('HTTP/1.1 403 Forbidden');

	echo 'PlainIB is already installed. ',
		'To re-run the installer, delete or move config.php.';

	exit;
}

$app['session']['installer'] = true;

$app['path'] = function () use ($app) {
	return new Path_QueryString($app['request']);
};

$app['router'] = function () use ($app) {
	return new Router_Install($app['path']->get());
};

$view = new $app['router']->view($app);
echo $view->responseBody;

/// Internal redirect
function diverge($dest, $args = array()) {
	global $app;

	// missing slash
	if (substr($dest, 0, 1) !== '/')
		$dest = "/$goto";

	redirect($app['path']->create($dest, $args));
}
