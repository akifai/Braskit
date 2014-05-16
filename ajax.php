<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

require(dirname(__FILE__).'/inc/autoload.php');
AutoLoader::register();

$app = new App();

$app['controller'] = new Controller_Ajax($app);

$app->run();

// TODO
function diverge($dest, $args = array()) {
	global $ajax;

	$ajax['diverge'] = $dest;
	$ajax['divergeArgs'] = $args;
}
