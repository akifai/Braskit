<?php

define('TINYIB', null);

// don't load the config or database
define('TINYIB_INSTALLER', true);

// let us download the session-stored config without having session cookies set
ini_set('session.use_only_cookies', false);

require('inc/global_init.php');

if (file_exists(TINYIB_ROOT.'/config.php') && !isset($_SESSION['installer'])) {
	header('HTTP/1.1 403 Forbidden');

	echo 'PlainIB is already installed. ',
		'To re-run the installer, delete or move config.php.';

	exit;
}

$_SESSION['installer'] = true;

$path = new Path_QueryString();
$router = new Router_Install($path->get());

$view = new $router->view($router);
echo $view->requestBody;

/// Internal redirect
function diverge($dest, $args = array()) {
	global $path;

	// missing slash
	if (substr($dest, 0, 1) !== '/')
		$dest = "/$goto";

	redirect($path->create($dest, $args));
}
