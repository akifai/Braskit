<?php

/*
 * Usage:
 *
 *   // Check login
 *   $user = new UserLogin($username, $password);
 *
 *   // Create user
 *   $newUser = $user->create("username");
 *   $newUser->setPassword("password");
 *   $newUser->setLevel(9999);
 *   $newUser->commit();
 *
 *   // Edit user
 *   $target = $user->edit("username");
 *   $target->setUsername("new_username");
 *   $target->setPassword("password");
 *   $target->setLevel(9999);
 *   $target->commit();
 *
 *   // Delete user
 *   $user->delete("username");
 *
 * TODO:
 *   - Check permissions for actions
 *   - Test everything properly
 */

class User {
	protected $changes = array();

	public $username = false;
	public $password = false;
	public $lastlogin = 0;
	public $level = 0;
	public $email = '';
	public $capcode = '';

	public function __toString() {
		return $this->username;
	}

	public function create($username) {
		return new UserCreate($username, $this->level);
	}

	public function edit($id) {
		return new UserEdit($id, $this->level);
	}

	public function delete($username) {
		global $app;

		if ($this->username === $username)
			throw new UserException('You cannot delete yourself.');

		// TODO: Check if we have higher permissions than the user
		// we're deleting.

		$app['db']->deleteUser($username);
	}


	//
	// Modifiers
	//

	public function setEmail($email) {
		if (!strlen($email) || $email === $this->email)
			return;

		if ($email && filter_var($email, FILTER_VALIDATE_EMAIL) === false)
			throw new UserException('Invalid email address.');

		$this->email = $email;
		$this->changes[] = 'email';
	}

	public function setCapcode($capcode) {
		if (!strlen($capcode) || $capcode === $this->capcode)
			return;

		// TODO: Restrict to a subset of HTML

		$this->capcode = $capcode;
		$this->changes[] = 'capcode';
	}

	public function setLevel($level) {
		if ($level == $this->level)
			return;

		$this->level = (int)$level;
		$this->changes[] = 'level';
	}

	public function setPassword($password) {
		if (!strlen($password)) {
			return;
		}

		$this->password = password_hash($password, PASSWORD_DEFAULT);
		$this->changes[] = 'password';
	}

	public function commit() {
		global $app;

		if (!$this->changes) {
			return; // nothing to do
		}

		try {
			$app['db']->modifyUser($this);
		} catch (PDOException $e) {
			$err = $e->getCode();

			switch ($err) {
			case PgError::UNIQUE_VIOLATION:
				// Username collision
				throw new UserException("A user with that name already exists.");
				break;
			default:
				// Unknown error
				throw $e;
			}
		}
	}


	//
	// Internals
	//

	protected function requireLevel($level) {
		if ($this->level >= $level)
			return;

		throw new UserException("You don't have sufficient permissions.");
	}

	protected function checkSuspension() {
		if ($this->level < 1) {
			throw new UserException('User account is suspended.');
		}
	}

	/**
	 * Loads a user account by its username.
	 *
	 * @param string Username
	 */
	protected function load($username) {
		global $app;

		$row = $app['db']->getUser($username);

		if ($row === false)
			throw new UserException("No such user exists.");

		$this->username = $row->username;
		$this->password = $row->password;
		$this->lastlogin = $row->lastlogin;
		$this->level = $row->level;
		$this->email = $row->email;
		$this->capcode = $row->capcode;
	}


	//
	// Cryptography
	//

	protected function checkPassword($password) {
		$hash = $this->password;

		if (!password_verify($password, $hash)) {
			return false;
		}

		if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
			$this->setPassword($password);
			$this->commit();
		}

		return true;
	}


	//
	// Static API
	//

	public static function get($username) {
		global $app;

		return $app['db']->getUser($username);
	}

	public static function getAll() {
		global $app;

		return $app['db']->getUserList();
	}
}

class UserLogin extends User {
	public function __construct($username, $password) {
		try {
			$this->load($username);
		} catch (UserException $e) {
			throw new UserException('Invalid login.');
		}

		if (!$this->checkPassword($password)) {
			throw new UserException('Invalid login.');
		}

		$this->checkSuspension();
	}

	public function __wakeup() {
		$hash = $this->password;

		// Things might change between requests. Reload everything.
		$this->load($this->username);

		// Just in case...
		// remember, $this->load() replaces $this->password, so $hash
		// and $this->password aren't necessarily equal
		if (!$hash || !$this->password) {
			throw new RuntimeException('Cannot restore user session.');
		}

		// Validate session password with database password
		if ($hash !== $this->password) {
			throw new UserException('Invalid login.');
		}

		$this->checkSuspension();
	}
}

class UserAdmin extends User {
	public function __construct() {}

	// we always have the highest permissions
	public $level = 9999;

	// doesn't throw an exception, so it bypasses all the checks
	protected function requireLevel($level) {}
}

class UserCreate extends User {
	protected $self_level = 0;

	public function __construct($username, $self_level = false) {
		$this->username = $username;
		$this->self_level = $self_level === false ? 9999 : $self_level;
	}

	public function commit() {
		global $app;

		try {
			$app['db']->insertUser($this);
		} catch (PDOException $e) {
			$err = $e->getCode();

			switch ($err) {
			case PgError::UNIQUE_VIOLATION:
				// Username collision
				throw new Exception("A user with that name already exists.");
				break;
			default:
				// Unknown error
				throw $e;
			}
		}
	}
}

class UserEdit extends User {
	protected $self_level = 0;

	public $newUsername = '';

	/// @todo check if we have permission to edit this user
	public function __construct($username, $self_level) {
		$this->load($username);

		$this->newUsername = $this->username;
	}

	public function setUsername($username) {
		if (!strlen($username))
			return;

		$this->newUsername = $username;

		$this->changes[] = 'username';
	}
}
