<?php
defined('TINYIB') or exit;

function liftban_get($url) {
	do_csrf($url);
}

function liftban_post($url) {
	$user = do_login('/bans');

	do_csrf();

	if (!isset($_POST['ban'])) {
		diverge('/bans');
		return;
	}

	$bans = is_array($_POST['ban']) ? $_POST['ban'] : array($_POST['ban']);

	foreach ($bans as $ban)
		deleteBanByID($ban);

	diverge('/bans');
}
