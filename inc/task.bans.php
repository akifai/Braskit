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

	$ip = isset($_GET['ip']) ? $_GET['ip'] : false;

	echo render('bans.html', array(
		'admin' => true,
		'bans' => $bans,
		'ip' => $ip,
	));
}
