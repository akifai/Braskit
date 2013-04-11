<?php
defined('TINYIB') or exit;

class Config {
	protected $config = array();
	protected $types = array();
	protected $changes = array();

	const CACHE_KEY = 'global_config';

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
		if (isset($this->config[$key]))
			return $this->config[$key];

		throw new Exception("Unknown configuration key '$key'.");
	}

	public function __set($key, $value) {
		$this->config[$key] = $value;
		$this->changes[] = $key;
	}

	public function __unset($key) {
		deleteConfigValue($key);
	}

	protected function loadStandardConfig() {
		require(TINYIB_ROOT.'/inc/global_config.php');
		$config = get_defined_vars();

		foreach ($config as $key => $value) {
			$this->types[$key] = gettype($value);
			$this->config[$key] = $value;
		}
	}

	protected function loadSQLConfig() {
		// get config from sql
		$config = loadGlobalConfig();

		if (is_array($config))
			$this->config = array_merge($config, $this->config);
	}

	protected function loadFromCache() {
		$cache = get_cache(self::CACHE_KEY);

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

		set_cache(self::CACHE_KEY, $cache);
	}
}
