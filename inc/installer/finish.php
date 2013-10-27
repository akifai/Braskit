<?php
defined('TINYIB') or exit;

function finish_get() {
	// we don't belong here yet
	if (!file_exists(TINYIB_ROOT.'/config.php')) {
		if (!isset($_SESSION['installer_secret'])) {
			diverge('/');
		} else {
			diverge('/config');
		}

		return;
	}

	// load config
	require(TINYIB_ROOT.'/config.php');

	// this makes sure that the person who placed config.php in the root dir
	// is the same person finishing the install
	if ($_SESSION['installer_secret'] !== $secret)
		throw new Exception('Fuck off.');

	// connect to database
	require_once(TINYIB_ROOT.'/inc/database.php');

	// lazy hack: globalise the variables which are supposed to be global
	$GLOBALS += get_defined_vars();

	$dbh->beginTransaction();

	initDatabase();

	// create our user account
	$user = new UserAdmin();

	$u = $user->create($_SESSION['installer_user']);
	$u->setPassword($_SESSION['installer_pass']);
	$u->setLevel(9999);
	$u->commit();

	// if something fails, nothing is committed to the database
	$dbh->commit();

	// and we're done! clear our session and redirect
	$_SESSION = array();
	redirect('board.php?/login');
}
