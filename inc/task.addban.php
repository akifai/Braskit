<?php
defined('TINYIB_BOARD') or exit;
require 'inc/class.IP.php';

function addban_post() {
	list($loggedin) = manageCheckLogIn();

	if (!$loggedin) {
		redirect(get_script_name().'?task=login&nexttask=bans');
		return;
	}

	if (!isset($_POST['ip']) || !$_POST['ip'])
		make_error('No IP address entered');

	$ip = trim($_POST['ip']);

	// TODO: Make a wrapper function for this perhaps?
	try {
		$iplib = new IP($ip);

		// If we've made it so far, then the IP is valid.
		$ip = $iplib->toShort();
	} catch (Exception $e) {
		make_error($e->getMessage());
	}

	$reason = isset($_POST['reason']) ? $_POST['reason'] : '';

	// Ban expiration
	if (!isset($_POST['expire']) && ctype_digit($_POST['expire']))
		$expire = $_SERVER['REQUEST_TIME'] + $_POST['expire'];
	else
		$expire = 0;

	$ban = array(
		'ip' => $ip,
		'reason' => $reason,
		'expire' => $expire,
	);
	insertBan($ban);

	redirect(get_script_name().'?task=bans');
}
