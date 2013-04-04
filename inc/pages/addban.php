<?php
defined('TINYIB') or exit;

function addban_post($url) {
	$user = do_login($url);

	$flags = PARAM_DEFAULT ^ PARAM_GET; // no gets

	$expire = param('expire', $flags);
	$reason = param('reason', $flags);
	$ip = param('ip', $flags);

	// remove whitespace
	$ip = trim($ip);

	if ($ip === '')
		throw new Exception('No IP entered.');

	// get the short form of the ip (i.e., 127.000.000.001 -> 127.0.0.1)
	$iplib = new IP($ip);
	$ip = $iplib->toShort();

	// Ban expiration
	if ($expire && ctype_digit($expire)) {
		// expiry time + request time = when the ban expires
		$expire += $_SERVER['REQUEST_TIME'];
	} else {
		// never expire
		$expire = 0;
	}

	$ban = array(
		'ip' => $ip,
		'reason' => $reason,
		'expire' => $expire,
	);
	insertBan($ban);

	diverge('/bans');
}
