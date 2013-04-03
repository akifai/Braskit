<?php
defined('TINYIB') or exit;

function login_get() {
	$loggedin = check_login();

	// we're logged in already
	if ($loggedin) {
		if (isset($_GET['nexttask']))
			redirect(get_script_name().'?task='.$_GET['nexttask']);
		else
			redirect(get_script_name().'?task=manage');

		return;
	}

	$error = false;

	// get the login error, if any
	if (isset($_SESSION['login_error'])) {
		$error = $_SESSION['login_error'];
		unset($_SESSION['login_error']);
	}

	echo render('login.html', array('error' => $error));
}

function login_post() {
	$loggedin = check_login();

	if ($loggedin) {
		if (isset($_GET['nexttask']))
			redirect(get_script_name().'?task='.$_GET['nexttask']);
		else
			redirect(get_script_name().'?task=manage');

		return;
	}

	$_SESSION['login_error'] = 'Incorrect login.';

	redirect(get_script_name().'?task=login');
}
