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
	protected $id = false;
	protected $username = false;
	protected $password = false;
	protected $hashtype = false;
	protected $lastlogin = 0;
	protected $level = 0;
	protected $email = '';
	protected $capcode = '';

	public function __construct($username, $password) {
		$this->loadByUsername($username);

		if (!$this->checkPassword($password))
			throw new Exception('Invalid password.');
	}

	public function create() {
		return new UserCreate($this->level);
	}

	public function edit($id) {
		$target = new UserEdit($id, $this->level);
	}

	public function delete($id) {
		if ($this->id === $id)
			throw new Exception('You cannot delete yourself.');

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
			throw new Exception('Invalid email address.');

		$this->email = $email;
		$this->changes[] = 'email';
	}

	public function capcode($capcode = false) {
		if ($capcode === false)
			return $this->capcode;

		// TODO

		$this->capcode = $capcode;
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

		throw new Exception("You don't have sufficient permissions.");
	}


	/**
	 * @param int User ID
	 */
	protected function loadByID($id) {
		$user = getUserByID($id);

		if ($user === false)
			throw new Exception("No such user exists.");

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
			throw new Exception("No such user exists.");

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

	protected function checkPassword($key) {
		if ($this->password === false || $this->hashtype === false) {
			// shitty wording lol - this shouldn't happen anyway
			throw new Exception('Password not loaded.');
		}

		return $this->password === $this->hash($this->hashtype, $key);
	}

	/**
	 * Create an array for passing to a database function
	 */
	protected function createArray(Array $values) {
		$user = array();

		foreach ($values as $var) {
			if ($this->$var === false) {
				$variable = ucfirst($var);

				// false variables means something is missing
				throw new Exception("{$variable} isn't set.");
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
		throw new Exception("Couldn't generate password.");
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
	public function __construct() {}

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
	public function __construct() {}

	public function commit() {
		if (!$this->changes)
			return; // nothing to do

		$values = array_unique($this->changes);
		$user = $this->createArray($values);

		modifyUser($user);
	}
}
