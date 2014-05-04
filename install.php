<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

require(dirname(__FILE__).'/inc/class.autoload.php');
AutoLoader::register();

$app = new App();

$app['controller'] = new Controller_Install($app);

$app->run();

// TODO
function diverge($dest, $args = array()) {
	global $app;

	// missing slash
	if (substr($dest, 0, 1) !== '/') {
		$dest = "/$goto";
	}

	redirect($app['path']->create($dest, $args));
}
