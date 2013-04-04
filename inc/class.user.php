<?php
defined('TINYIB') or exit;

/*
 * Usage:
 *
 *   // Check login
 *   $user = new User($username, $password);
 *
 *   // Create user
 *   $newUser = $user->create();
 *   $newUser->username("username");
 *   $newUser->password("password");
 *   $newUser->level(9999);
 *   $id = $newUser->commit();
 *
 *   // Edit user
 *   $target = $user->edit($id);
 *   $target->username("username");
 *   $target->password("password");
 *   $target->level(9999);
 *   $target->commit();
 *
 *   // Delete user
 *   $user->delete($id);
 *
 * TODO:
 *   - Check permissions for actions
 *   - Test everything properly
 */

class User {
	protected $hashed = false;

	protected $id = false;
	protected $username = false;
	protected $password = false;
	protected $hashtype = false;
	protected $lastlogin = 0;
	protected $level = 0;
	protected $email = '';
	protected $capcode = '';

	public function __construct($username, $password) {
		try {
			$this->loadByUsername($username);
		} catch (UserException $e) {
			throw new UserException('Invalid login.');
		}

		if (!$this->checkPassword($password))
			throw new UserException('Invalid login.');
	}

	public function __wakeup() {
		// Things might change between requests. Reload everything.
		$this->loadByID($this->id);

		// Just in case...
		if ($this->hashed === false || $this->password === false)
			throw new UserException('Cannot restore user session.');

		// Validate password
		if ($this->hashed !== $this->password)
			throw new UserException('Invalid password.');
	}

	public function create() {
		return new UserCreate($this->level);
	}

	public function edit($id) {
		return new UserEdit($id, $this->level);
	}

	public function delete($id) {
		if ($this->id === $id)
			throw new UserException('You cannot delete yourself.');

		return deleteUserByID($id);
	}


	//
	// Accessors
	//

	protected $changes = array();

	public function email($email = false) {
		if ($email === false)
			return $this->email;

		if (filter_var($email, FILTER_VALIDATE_EMAIL) === false)
			throw new UserException('Invalid email address.');

		$this->email = $email;
		$this->changes[] = 'email';
	}

	public function capcode($capcode = false) {
		if ($capcode === false)
			return $this->capcode;

		// TODO

		$this->capcode = $capcode;
		$this->changes[] = 'capcode';
	}

	public function level($level = false) {
		if ($level === false)
			return $this->level;

		$this->level = (int)$level;
		$this->changes[] = 'level';
	}

	public function username($username = false) {
		if ($username === false)
			return $this->username;

		$this->username = $username;
		$this->changes[] = 'username';
	}

	public function password($password) {
		// there's no reason to return the password

		// we're going to assume this is failsafe
		$bits = $this->generateHash($password);

		$this->hashtype = $bits[0];
		$this->password = $bits[1];

		$this->changes[] = 'password';
	}


	//
	// Internals
	//

	protected function requireLevel($level) {
		if ($this->level >= $level)
			return;

		throw new UserException("You don't have sufficient permissions.");
	}


	/**
	 * @param int User ID
	 */
	protected function loadByID($id) {
		$user = getUserByID($id);

		if ($user === false)
			throw new UserException("No such user exists.");

		$this->setVariables($user);
	}

	/**
	 * Loads a user account by its username.
	 *
	 * @param string Username
	 */
	protected function loadByUsername($username) {
		$user = getUserByName($username);

		if ($user === false)
			throw new UserException("No such user exists.");

		$this->setVariables($user);
	}

	/**
	 * @param array Table row containing user information
	 */
	protected function setVariables($row) {
		$this->id = $row['id'];
		$this->username = $row['username'];
		$this->password = $row['password'];
		$this->hashtype = $row['hashtype'] ?: 'plaintext';
		$this->lastlogin = $row['lastlogin'];
		$this->level = $row['level'];
		$this->email = $row['email'];
		$this->capcode = $row['capcode'];
	}

	protected function checkPassword($key = false) {
		if ($this->password === false || $this->hashtype === false) {
			// shitty wording lol - this shouldn't happen anyway
			throw new UserException('Password not loaded.');
		}

		$hashed = $this->hash($this->hashtype, $key);

		if ($this->password === $hashed) {
			// store the hash so we can validate it in __wakeup
			$this->hashed = $hashed;

			return true;
		}

		return false;
	}

	/**
	 * Create an array for passing to a database function
	 */
	protected function createArray(Array $values) {
		$user = array();

		foreach ($values as $var) {
			if ($this->$var === false) {
				// false variables means something is missing
				throw new UserException("{$var} isn't set.");
			}

			$user[$var] = $this->$var;
		}

		return $user;
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
		global $secret;

		$method = 'hash_'.$algorithm;

		if (!method_exists(__CLASS__, $method))
			return false; // unknown algorith - die silently

		return call_user_func(__CLASS__.'::'.$method, $key);
	}

	/**
	 * Order in which to try hash functions when generating a password.
	 */
	protected static $hash_functions = array('sha256', 'sha1');

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
		throw new UserException("Couldn't generate password.");
	}

	protected static function hash_plaintext($key) {
		return (string)$key; // loool
	}

	protected static function hash_sha1($key) {
		global $secret;

		return sha1($key.$secret);
	}

	protected static function hash_sha256($key) {
		global $secret;

		// http://www.hardened-php.net/suhosin/a_feature_list.html
		if (function_exists('sha256'))
			return sha256($key.$secret);

		// php's documentation is really vague, so i'm going to assume
		// that sha256 might not always be available
		return @hash('sha256', $key);
	}
}

class UserNologin extends User {
	public function __construct() {}

	// we always have the highest permissions
	protected $level = 9999;

	// doesn't throw an exception, so it bypasses all the checks
	protected function requireLevel($level) {}
}

class UserCreate extends User {
	protected $self_level = 0;

	public function __construct($self_level) {
		$this->self_level = $self_level;
	}

	public function commit() {
		$user = $this->createArray(array(
			'username',
			'password',
			'hashtype',
			'lastlogin',
			'level',
			'email',
			'capcode',
		));

		return insertUser($user);
	}
}

class UserEdit extends User {
	protected $self_level = 0;

	public function __construct($id, $self_level) {
		$this->id = $id;

		// TODO - check if we have permission to edit this user
	}

	public function commit() {
		if (!$this->changes)
			return; // nothing to do

		$values = array_unique($this->changes);
		$values[] = 'id';

		$user = $this->createArray($values);

		modifyUser($user);
	}
}

class UserException extends Exception {}
