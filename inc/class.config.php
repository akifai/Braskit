<?php
defined('TINYIB') or exit;

/**
  * @todo: __unset()/toggling the default value and the SQL-stored one
  */
abstract class Config {
	// these must be set in subclasses
	protected $standard_config;
	protected $cache_key;
	protected $config_table_prefix;

	protected $config = array();
	protected $types = array();
	protected $changes = array();

	public function __construct() {
		// try loading from cache
		if ($this->loadFromCache())
			return;

		// predefined configuration
		$this->loadStandardConfig();

		// load SQL config
		$this->loadSQLConfig();

		// save to cache
		$this->saveToCache();
	}

	public function __isset($key) {
		// twig needs this
		return isset($this->config[$key]);
	}

	public function __get($key) {
		$this->checkKey($key);

		return $this->config[$key];
	}

	public function __set($key, $value) {
		$this->checkKey($key);

		// convert value to the correct type
		settype($value, $this->types[$key]);

		$this->config[$key] = $value;

		if (!in_array($key, $this->changes))
			$this->changes[] = $key;
	}

	// we don't use __destruct() because it can't handle thrown exceptions
	public function save() {
		// no changes made
		if (!$this->changes)
			return;

		// cache will be regenerated on next instance
		delete_cache($this->cache_key);

		// make an assoc array with the updated values
		$values = array();
		foreach ($this->changes as $key)
			$values[$key] = $this->config[$key];

		saveConfig($this->config_table_prefix, $values);

		$this->changes = array();
	}

	protected function checkKey($key) {
		if (isset($this->config[$key]))
			return;

		throw new Exception("Unknown configuration key '$key'.");
	}

	protected function loadStandardConfig() {
		require(TINYIB_ROOT.'/inc/'.$this->standard_config);
		$config = get_defined_vars();

		foreach ($config as $key => $value) {
			$this->types[$key] = gettype($value);
			$this->config[$key] = $value;
		}
	}

	protected function loadSQLConfig() {
		// get config from sql
		$config = loadConfig($this->config_table_prefix);

		if (is_array($config))
			$this->config = array_merge($this->config, $config);
	}

	protected function loadFromCache() {
		$cache = get_cache($this->cache_key);

		if (is_array($cache)) {
			$this->types = $cache['types'];
			$this->config = $cache['config'];

			return true;
		}

		return false;
	}

	protected function saveToCache() {
		$cache = array(
			'config' => $this->config,
			'types' => $this->types,
		);

		set_cache($this->cache_key, $cache);
	}
}

class GlobalConfig extends Config {
	protected $standard_config = 'global_config.php';
	protected $cache_key = '_global_config';
	protected $config_table_prefix = '';
}

class BoardConfig extends Config {
	protected $standard_config = 'board_config.php';

	public function __construct($board) {
		$this->cache_key = $board.'_config';
		$this->config_table_prefix = (string)$board;

		parent::__construct();
	}
}
