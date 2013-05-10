<?php
defined('TINYIB') or exit;

function start_get($url) {
	if (isset($_SESSION['install_config'])) {
		diverge('/config');
		return;
	}

	echo render('install.html');
}

function start_post($url) {
	require('inc/config_template.php');

	// set up config variables
	$vars = array();

	foreach (array('db_driver', 'db_name', 'db_username', 'db_password',
	'db_host', 'db_prefix', 'username', 'password') as $name) {
		$vars[$name] = param($name);

		// fix undefined variable caused by tampering or bugs
		if ($vars[$name] === false || $vars[$name] === null)
			$vars[$name] = '';
	}

	// generate a secret key
	$_SESSION['installer_secret'] = $vars['secret'] = random_string(65);

	// note: we use sessions to store the config because we don't want
	// other people to see the finished config!
	$_SESSION['install_config'] = @create_config($vars);

	// we need these for the last step
	$_SESSION['installer_user'] = $vars['username'];
	$_SESSION['installer_pass'] = $vars['password'];

	diverge('/config');
}
