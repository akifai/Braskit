<?php

class Database extends PDO {
	public static $time = 0;
	public static $queries = 0;

	protected $driver;
	protected $name;
	protected $host;
	protected $user;
	protected $pass;

	protected $dsn;

	public function __construct($name, $host, $user, $pass, $dsn = false) {
		$this->name = $name;
		$this->host = $host;
		$this->user = $user;
		$this->pass = $pass;

		if ($this->dsn)
			$this->dsn = $dsn;
		else
			$this->create_dsn();

		$this->spawn();
	}

	public function query($query) {
		$time = microtime(true);

		$sth = parent::query($query);

		self::addTime($time);
		self::$queries++;

		return $sth;
	}

	protected function create_dsn() {
		$this->dsn = 'pgsql:dbname='.$this->name;

		if ($this->host === (string)$this->host && $this->host) {
			$this->dsn .= ';host='.$this->host;
		}
	}

	/**
	 * Call PDO's __construct() method and return the resulting PDO object
	 */
	protected function spawn() {
		$options = array();

		// throw exceptions on error
		$options[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;

		// use our custom class when creating statement handles
		$options[PDO::ATTR_STATEMENT_CLASS] = array('DBStatement', array($this));

		// return associative arrays when fetch()ing
		$options[PDO::ATTR_DEFAULT_FETCH_MODE] = PDO::FETCH_ASSOC;

		// use real prepared statements
		$options[PDO::ATTR_EMULATE_PREPARES] = false;

		return parent::__construct($this->dsn, $this->user, $this->pass, $options);
	}

	public static function addTime($time) {
		self::$time += microtime(true) - $time;
	}
}

class DBStatement extends PDOStatement {
	protected $dbh;

	protected function __construct($dbh) {
		$this->dbh = $dbh;
	}

	public function execute($params = null) {
		$time = microtime(true);

		$sth = parent::execute($params);

		Database::addTime($time);
		Database::$queries++;

		return $sth;
	}
}
