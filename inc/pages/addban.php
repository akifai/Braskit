<?php
defined('TINYIB') or exit;

function addban_get($url) {
	do_csrf($url);
}

function addban_post($url) {
	$user = do_login('/bans');

	do_csrf();

	$flags = PARAM_DEFAULT ^ PARAM_GET; // no gets

	$expire = param('expire', $flags);
	$reason = param('reason', $flags);
	$ip = param('ip', $flags);

	// remove whitespace
	$ip = trim($ip);

	if ($ip === '')
		throw new Exception('No IP entered.');

	// Ban expiration
	if ($expire && ctype_digit($expire)) {
		// expiry time + request time = when the ban expires
		$expire += $_SERVER['REQUEST_TIME'];
	} else {
		// never expire
		$expire = null;
	}

	$ban = array(
		'ip' => $ip,
		'reason' => $reason,
		'expire' => $expire,
	);

	try {
		insertBan($ban);
	} catch (PDOException $e) {
		$errcode = $e->getCode();

		if ($errcode === '22P02') {
			throw new Exception("Invalid IP address.");
		} else {
			throw $e;
		}
	}

	diverge('/bans');
}
