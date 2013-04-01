<?php

define('TINYIB', null);

// don't load the config or database
define('TINYIB_INSTALLER', true);

// let us download the session-stored config without having session cookies set
ini_set('session.use_only_cookies', false);

require('inc/global_init.php');

if (file_exists(TINYIB_ROOT.'/config.php')) {
	header('HTTP/1.1 403 Forbidden');
	echo 'PlainIB is already installed. ',
		'To re-run the installer, delete or move config.php.';
	exit;
}

$task = param('p');

if ($task === 'restart' && isset($_SESSION['install_config']))
	// delete the saved config
	unset($_SESSION['install_config']);
elseif ($task === 'download' && isset($_SESSION['install_config'])) {
	// download the config
	header('Content-Disposition: attachment; filename=config.php');
	echo $_SESSION['install_config'];
	exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	require('inc/config_template.php');

	// set up config variables
	$vars = array();

	foreach (array('db_driver', 'db_name', 'db_username', 'db_password',
	'db_host', 'db_prefix') as $name) {
		$vars[$name] = param($name);

		// fix undefined variable caused by tampering or bugs
		if ($vars[$name] === false || $vars[$name] === null)
			$vars[$name] = '';
	}

	// generate a secret key
	$vars['secret'] = random_string(65);

	// note: we use sessions to store the config because we don't want
	// other people to see the finished config!
	$_SESSION['install_config'] = @create_config($vars);

	redirect(get_script_name());
} else {
	if (!isset($_SESSION['install_config'])) {
		// show installer
		echo render('install.html');
		exit;
	}

	// install's done - we demand that the config file be uploaded manually
	// for security reasons
	echo render('install_done.html', array(
		'config' => $_SESSION['install_config'],
		'session_name' => session_name(),
		'session_id' => session_id(),
	));
}
