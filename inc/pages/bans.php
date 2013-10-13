<?php
defined('TINYIB') or exit;

function bans_get($url) {
	$user = do_login($url);

	// TODO: Pagination
	$bans = allBans();

	$ip = param('ip');

	echo render('bans.html', array(
		'admin' => true,
		'bans' => $bans,
		'ip' => $ip,
	));
}
