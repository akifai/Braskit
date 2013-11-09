<?php
defined('TINYIB') or exit;

function bans_get($url) {
	$user = do_login($url);

	if (param('ip') || param('lift', PARAM_DEFAULT | PARAM_ARRAY)) {
		do_csrf();
		return;
	}

	// TODO: Pagination
	$bans = allBans();

	$ip = param('ip');

	echo render('bans.html', array(
		'admin' => true,
		'bans' => $bans,
		'ip' => $ip,
	));
}

function bans_post($url) {
	$user = do_login($url);
	do_csrf();

	// adding a ban
	$expire = param('expire');
	$reason = param('reason');
	$ip = param('ip');

	if ($ip) {
		$ban = new BanCreate($ip);
		$ban->setReason($reason);
		$ban->setExpire($expire);

		$ban->add();
	}

	// lifting bans
	$lifts = param('lift', PARAM_DEFAULT | PARAM_ARRAY);

	if ($lifts && !is_array($lifts)) {
		$lifts = array($lifts);
	}

	foreach ($lifts as $id) {
		Ban::delete($id);
	}

	diverge('/bans');
}
