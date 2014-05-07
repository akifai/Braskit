<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

class View_Login extends View {
	protected function get($app) {
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
		if (isset($app['session']['login_error'])) {
			$error = $app['session']['login_error'];
			unset($app['session']['login_error']);
		}

		$goto = $app['param']->get('goto');

		return $this->render('login.html', array(
			'error' => $error,
			'goto' => $goto,
		));
	}

	protected function post($app) {
		$param = $app['param']->flags(Param::T_STRING | Param::M_POST);

		$username = $param->get('login_user');
		$password = $param->get('login_pass');

		try {
			// validate user/pw
			$user = new UserLogin($username, $password);

			// this keeps us logged in
			$app['session']['login'] = serialize($user);

			$loggedin = true;
		} catch (UserException $e) {
			$loggedin = false;

			// store the error message we display after the redirect
			$app['session']['login_error'] = $e->getMessage();
		}

		if ($loggedin) {
			$goto = $param->get('goto', Param::S_DEFAULT);
			redirect_after_login($goto);

			exit;
		}

		diverge('/login');
	}
}
