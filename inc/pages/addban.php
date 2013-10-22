<?php
defined('TINYIB') or exit;

function addban_get($url) {
	do_csrf($url);
}

function addban_post($url) {
	$user = do_login('/bans');
	do_csrf();

	$flags = PARAM_DEFAULT & ~PARAM_GET; // no gets

	$expire = param('expire', $flags);
	$reason = param('reason', $flags);
	$ip = param('ip', $flags);

	$ban = new BanCreate($ip);
	$ban->setReason($reason);
	$ban->setExpire($expire);

	$ban->add();

	diverge('/bans');
}
