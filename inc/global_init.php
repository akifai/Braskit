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

if (PHP_SAPI === 'cli' && !defined('TINYIB'))
	define('TINYIB', null);

defined('TINYIB') or exit;

ignore_user_abort(true);

// sessions
if (!defined('TINYIB_NO_SESSIONS') || TINYIB_NO_SESSIONS) {
	session_name('TINYIB');
	session_start();
}

date_default_timezone_set('Europe/Berlin');

// some constants
define('TINYIB_ROOT', realpath(dirname(__FILE__).'/..'));

// Load classes automagically
require(TINYIB_ROOT.'/inc/class.autoload.php');
AutoLoader::register();

// Misc functions
require(TINYIB_ROOT.'/inc/functions.php');

// Exception handlers rely on the above include
if (defined('TINYIB_EXCEPTION_HANDLER'))
	set_exception_handler(TINYIB_EXCEPTION_HANDLER);

if (get_magic_quotes_gpc()) {
	set_magic_quotes_runtime(false);
}

$app = new App();

$app['request'] = function () {
	return new Request();
};

$app['param'] = $app->factory(function () use ($app) {
	return new Param($app['request']);
});

$app['cache.debug'] = false;

// Cache
$app['cache'] = function () use ($app) {
	if ($app['cache.debug']) {
		return new Cache_Debug();
	}

	if (ini_get('apc.enabled') && extension_loaded('apc')) {
		return new Cache_APC();
	}
	
	return new Cache_PHP($app['path.cache']);
};

// setting for enabling debug features
$app['template.debug'] = false;

// returns a filesystem loader for inc/templates
$app['template.loader'] = function () {
	return new PlainIB_Twig_Loader(TINYIB_ROOT.'/inc/templates');
};

// returns a new chain loader
$app['template.chain'] = $app->factory(function () {
	return new Twig_Loader_Chain();
});

$app['template.creator'] = $app->protect(function ($loader) use ($app) {
	$twig = new Twig_Environment($loader, array(
		'cache' => $app['template.debug'] ? false : $app['path.cache'],
		'debug' => $app['template.debug'],
	));

	$twig->addExtension(new PlainIB_Twig_Extension());

	// Load debugger
	if ($app['template.debug']) {
		$twig->addExtension(new Twig_Extension_Debug());
	}

	return $twig;
});

$app['template'] = function () use ($app) {
	return $app['template.creator']($app['template.loader']);
};

$app['js.debug'] = false;

// bunch of stuff that was previously defined only in the generated config file
$app['js.includes'] = array(
	'jquery-1.9.0.min.js',
	'jquery.cookie.js',
	'bootstrap.min.js',
	'spin.js',
	'PlainIB.js',
);

$app['less.debug'] = false;

$app['less.stylesheets'] = array(
	'Burichan' => 'burichan',
	'Futaba' => 'futaba',
	'Tomorrow' => 'tomorrow',
	'Yotsuba' => 'yotsuba',
	'Yotsuba B' => 'yotsuba-b',
);

$app['less.default_style'] = 'Futaba';

$app['thumb.method'] = 'gd';

$app['path.tmp'] = sys_get_temp_dir();
$app['path.cache'] = TINYIB_ROOT.'/cache';

if (defined('TINYIB_INSTALLER') && TINYIB_INSTALLER) {
	// we can't use a config or database for this entry point
	return;
}

if (file_exists(TINYIB_ROOT.'/config.php')) {
	// Load the config
	require(TINYIB_ROOT.'/config.php');
} else {
	// no config == not installed
	redirect('install.php');
	exit;
}

// establish database connection
$app['dbh'] = function () use ($app) {
	return new DBConnection(
		$app['db.name'],
		$app['db.host'],
		$app['db.username'],
		$app['db.password']
	);
};

$app['db'] = function () use ($app) {
	return new Database($app['dbh'], $app['db.prefix']);
};

// Site configuration
$app['config'] = function () {
	return new GlobalConfig();
};
