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

		switch ($errcode) {
		case PgError::INVALID_TEXT_REPRESENTATION:
			// pgsql says the IP was not valid!
			throw new Exception("Invalid IP address.");
			break;
		case PgError::UNIQUE_VIOLATION:
			// do nothing
			break;
		default:
			// unexpected error
			throw $e;
		}
	}

	diverge('/bans');
}
