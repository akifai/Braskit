<?php
defined('TINYIB') or exit;

function config_get($url) {
	if (!isset($_SESSION['install_config'])) {
		// we haven't even begun
		diverge('/');
		exit;
	}

	$session_name = session_name();
	$session_id = session_id();

	$config_path = expand_path('get_config', array(
		$session_name => $session_id
	));

	echo render('install_config.html', array(
		'config' => $_SESSION['install_config'],
		'config_path' => $config_path,
		'config_url' => get_url($config_path),
		'session_name' => $session_name,
		'session_id' => $session_id,
	));
}
