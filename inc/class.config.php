<?php

/**
  * @todo: __unset()/toggling the default value and the SQL-stored one
  */
abstract class Config implements Iterator {
	// these must be set in subclasses
	protected $standard_config;
	protected $cache_key;
	protected $db_key = null;

	protected $config = array();
	protected $changes = array();
	protected $deletions = array();

	// for iterator
	protected $pos = 0;
	protected $keys = array();

	public function __construct() {
		// try loading from cache
		if ($this->loadFromCache())
			return;

		// predefined configuration
		$this->loadStandardConfig();

		// load SQL config
		$this->loadSQLConfig();

		// num => string map for iterator
		$this->keys = array_keys($this->config);

		// save to cache
		$this->saveToCache();
	}

	public function __isset($key) {
		// twig needs this
		return isset($this->config[$key]);
	}

	public function __get($key) {
		$this->checkKey($key);

		return $this->config[$key]['value'];
	}

	public function __set($key, $value) {
		$this->checkKey($key);

		// convert value to the correct type
		settype($value, $this->config[$key]['type']);

		// make sure integers are non-negative
		if ($this->config[$key]['type'] === 'integer')
			$value = abs($value);

		// check if same as current value and return if so
		if ($this->config[$key]['value'] === $value)
			return;

		$this->config[$key]['value'] = $value;

		// check if same as default value and flag for deletion if so
		if (isset($this->config[$key]['default'])
		&& $this->config[$key]['default'] === $value) {
			if (!in_array($key, $this->deletions))
				$this->deletions[] = $key;

			return;
		}

		if (!in_array($key, $this->changes))
			$this->changes[] = $key;
	}

	public function __unset($key) {
		$this->checkKey($key);

		$this->deletions[] = $key;
	}

	// we don't use __destruct() because it can't handle thrown exceptions
	public function save() {
		// no changes made
		if (!$this->changes && !$this->deletions)
			return;

		// cache will be regenerated on next instance
		delete_cache($this->cache_key);

		// make an assoc array with the updated values
		$values = array();
		foreach ($this->changes as $key)
			$values[$key] = $this->config[$key]['value'];

		saveConfig($this->db_key, $values);

		$this->changes = array();

		// do deletions
		deleteConfigKeys($this->db_key, $this->deletions);

		$this->deletions = array();
	}

	protected function checkKey($key) {
		if (isset($this->config[$key]))
			return;

		throw new Exception("Unknown configuration key '$key'.");
	}

	protected function loadStandardConfig() {
		require(TINYIB_ROOT.'/inc/'.$this->standard_config);

		foreach ($this->config as $key => $value) {
			$this->config[$key]['modified'] = false;

			// for templates/iterator
			$this->config[$key]['name'] = $key;
		}
	}

	protected function loadSQLConfig() {
		// get config from sql
		$config = loadConfig($this->db_key);

		if (!is_array($config))
			return;

		foreach ($config as $key => $value) {
			if (!isset($this->config[$key]))
				continue; // unknown setting

			// save default value
			$this->config[$key]['default'] = $this->config[$key]['value'];
			$this->config[$key]['modified'] = true;
			$this->config[$key]['value'] = $value;

			settype($this->config[$key]['value'], $this->config[$key]['type']);
		}
	}

	protected function loadFromCache() {
		$cache = get_cache($this->cache_key);

		if (is_array($cache)) {
			$this->config = $cache['config'];
			$this->keys = $cache['keys'];

			return true;
		}

		return false;
	}

	protected function saveToCache() {
		$cache = array('keys' => $this->keys, 'config' => $this->config);

		set_cache($this->cache_key, $cache);
	}


	//
	// Iterator
	//

	public function current() {
		return $this->config[$this->keys[$this->pos]];
	}

	public function valid() {
		return isset($this->keys[$this->pos]);
	}

	public function key() {
		return $this->pos;
	}

	public function next() {
		$this->pos++;
	}

	public function rewind() {
		$this->pos = 0;
	}
}

class GlobalConfig extends Config {
	protected $standard_config = 'global_config.php';
	protected $cache_key = '_global_config';
}

class BoardConfig extends Config {
	protected $standard_config = 'board_config.php';

	public function __construct($board) {
		$this->cache_key = $board.'_config';
		$this->db_key = (string)$board;

		parent::__construct();
	}
}
