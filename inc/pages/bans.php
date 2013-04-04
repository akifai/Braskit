<?php
defined('TINYIB') or exit;

function bans_get($url) {
	$user = do_login($url);

	// TODO: Pagination
	$bans = allBans();

	$ip = isset($_GET['ip']) ? $_GET['ip'] : false;

	echo render('bans.html', array(
		'admin' => true,
		'bans' => $bans,
		'ip' => $ip,
	));
}
