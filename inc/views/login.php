<?php

class View_Login extends View {
	protected function get($url) {
		$error = false;

		try {
			$user = do_login();
		} catch (UserException $e) {
			$user = false;
			$error = $e->getMessage();
		}

		if ($user) {
			redirect_after_login();
			return;
		}

		// get the login error, if any
		if (isset($_SESSION['login_error'])) {
			$error = $_SESSION['login_error'];
			unset($_SESSION['login_error']);
		}

		$goto = param('goto');

		return $this->render('login.html', array(
			'error' => $error,
			'goto' => $goto,
		));
	}

	protected function post($url) {
		list($username, $password) = get_login_credentials();

		try {
			// validate user/pw
			$user = new UserLogin($username, $password);

			// this keeps us logged in
			$_SESSION['login'] = serialize($user);

			$loggedin = true;
		} catch (UserException $e) {
			$loggedin = false;

			// store the error message we display after the redirect
			$_SESSION['login_error'] = $e->getMessage();
		}

		if ($loggedin) {
			$goto = param('goto');
			redirect_after_login($goto);

			exit;
		}

		diverge('/login');
	}
}
