<?php
defined('TINYIB_BOARD') or exit;

//function liftban_get() {
//}

function liftban_post() {
	list($loggedin, $isadmin) = manageCheckLogIn();

	if (!$loggedin) {
		redirect(get_script_name().'?task=login&nexttask=bans');
		return;
	}

	if (!isset($_POST['ban'])) {
		redirect(get_script_name().'?task=bans');
		return;
	}

	$bans = is_array($_POST['ban']) ? $_POST['ban'] : array($_POST['ban']);

	foreach ($bans as $ban)
		deleteBanByID($ban);

	redirect(get_script_name().'?task=bans');
}
