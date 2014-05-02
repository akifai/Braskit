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
	protected $changes = false;
	protected $hashed = false;

	public $username = false;
	public $password = false;
	public $hashtype = false;
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
		$this->changes = true;
	}

	public function setCapcode($capcode) {
		if (!strlen($capcode) || $capcode === $this->capcode)
			return;

		// TODO: Restrict to a subset of HTML

		$this->capcode = $capcode;
		$this->changes = true;
	}

	public function setLevel($level) {
		if ($level == $this->level)
			return;

		$this->level = (int)$level;
		$this->changes = true;
	}

	public function setPassword($password) {
		if (!strlen($password))
			return;

		// we're going to assume this is failsafe
		$bits = $this->generateHash($password);

		$this->hashtype = $bits[0];
		$this->password = $bits[1];

		$this->changes = true;
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
		$this->hashtype = $row->hashtype ?: 'plaintext';
		$this->lastlogin = $row->lastlogin;
		$this->level = $row->level;
		$this->email = $row->email;
		$this->capcode = $row->capcode;
	}

	protected function checkPassword($key = false) {
		if ($this->password === false || $this->hashtype === false) {
			// shitty wording lol - this shouldn't happen anyway
			throw new LogicException('Password not loaded.');
		}

		$hashed = $this->hash($this->hashtype, $key);

		if ($this->password === $hashed) {
			// store the hash so we can validate it in __wakeup
			$this->hashed = $hashed;

			return true;
		}

		return false;
	}


	//
	// Cryptography
	//
	// Neither one of these hash functions are particularly good for hashing
	// passwords. Something using crypt() would be nice, but that's overkill
	// imho.
	//

	/**
	 * Hashes a password based on a certain algorithm
	 * @return string|bool password hash or false on failure
	 */
	protected static function hash($algorithm, $key) {
		global $app;

		$method = 'hash_'.$algorithm;

		if (!method_exists(__CLASS__, $method))
			return false; // unknown algorith - die silently

		return call_user_func(__CLASS__.'::'.$method, $key);
	}

	/**
	 * Order in which to try hash functions when generating a password.
	 */
	protected static $hash_functions = array(
		'sha256',
		'sha1',
		'plaintext',
	);

	/**
	 * Generate a hash based on a plaintext key.
	 * @param string Key in plaintext.
	 * @return array Array consisting of the hash function used and the
	 *               hashed key.
	 */
	protected static function generateHash($key) {
		foreach (self::$hash_functions as $function) {
			$hashed = self::hash($function, $key);

			if ($hashed !== false)
				return array($function, $hashed);
		}

		// should never, ever happen
		throw new LogicException("Couldn't generate password.");
	}

	protected static function hash_plaintext($key) {
		return (string)$key; // loool
	}

	protected static function hash_sha1($key) {
		global $app;

		return sha1($key.$app['secret']);
	}

	protected static function hash_sha256($key) {
		global $app;

		// http://www.hardened-php.net/suhosin/a_feature_list.html
		if (function_exists('sha256'))
			return sha256($key.$app['secret']);

		// php's documentation is really vague, so i'm going to assume
		// that sha256 might not always be available
		return @hash('sha256', $key.$app['secret']);
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

		if (!$this->checkPassword($password))
			throw new UserException('Invalid login.');

		$this->checkSuspension();
	}

	public function __wakeup() {
		// Things might change between requests. Reload everything.
		$this->load($this->username);

		// Just in case...
		if ($this->hashed === false || $this->password === false)
			throw new LogicException('Cannot restore user session.');

		// Validate password
		if ($this->hashed !== $this->password)
			throw new UserException('Invalid password.');

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

		$this->changes = true;
	}

	public function commit() {
		global $app;

		if (!$this->changes)
			return; // nothing to do

		try {
			$app['db']->modifyUser($this);
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
