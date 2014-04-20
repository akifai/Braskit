<?php

class View_Users extends View {
	protected function get($url, $username = false) {
		global $db;

		$user = do_login($url);

		$vars = array(
			'admin' => true,
			'editing' => false,
			'user' => $user,
		);

		if ($username === false) {
			$vars['users'] = $db->getUserList();
		} else {
			$vars['editing'] = true;
			$vars['target'] = $user->edit($username);
		}

		return $this->render('users.html', $vars);
	}

	protected function post($url, $username = false) {
		$user = do_login($url);

		do_csrf();

		// Form parameters
		$new_username = trim(param('username'));
		$email = trim(param('email'));
		$password = trim(param('password'));
		$password2 = trim(param('password2'));
		$level = abs(trim(param('level')));

		if ($username !== false) {
			// Edit user
			$target = $user->edit($username);

			$target->setUsername($new_username);

			// Set new password if it's not blank in the form
			if ($password !== '')
				$target->setPassword($password);

			$target->setEmail($email);
			$target->setLevel($level);
		} else {
			// Add user
			$target = $user->create($new_username);

			// Check password
			if ($password === '' || $password !== $password2)
				throw new Exception('Invalid password');

			$target->setEmail($email);
			$target->setPassword($password);
			$target->setLevel($level);
		}

		$target->commit();

		diverge('/users');
	}
}
