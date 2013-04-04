<?php
defined('TINYIB') or exit;

//function liftban_get() {
//}

function liftban_post($url) {
	$user = do_login($url);

	if (!isset($_POST['ban'])) {
		diverge('/bans');
		return;
	}

	$bans = is_array($_POST['ban']) ? $_POST['ban'] : array($_POST['ban']);

	foreach ($bans as $ban)
		deleteBanByID($ban);

	diverge('/bans');
}
