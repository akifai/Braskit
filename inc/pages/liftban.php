<?php
defined('TINYIB') or exit;

function liftban_get($url) {
	do_csrf($url);
}

function liftban_post($url, $num = false) {
	$user = do_login('/bans');

	do_csrf();

	if ($num) {
		$bans = array($num);
	} else {
		$flags = PARAM_DEFAULT | PARAM_ARRAY | PARAM_STRICT;
		$bans = param('ban', $flags);

		if (!is_array($bans))
			$bans = array($bans);
	}

	foreach ($bans as $ban)
		if (ctype_digit($ban))
			deleteBanByID($ban);

	diverge('/bans');
}
