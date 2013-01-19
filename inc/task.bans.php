<?php
defined('TINYIB_BOARD') or exit;

function bans_get() {
	list($loggedin, $isadmin) = manageCheckLogIn();

	if (!$loggedin) {
		redirect(get_script_name().'?task=login&nexttask=bans');
		return;
	}

	// TODO: Pagination
	$bans = allBans();

	echo render('bans.html', array(
		'admin' => true,
		'bans' => $bans,
	));
}
