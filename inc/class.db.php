<?php
defined('TINYIB') or exit;

class Database extends PDO {
	public static $time = 0;
	public static $queries = 0;

	private $driver, $name, $host, $user, $pass;

	const DSN_FORMAT = '%s:dbname=%s;host=%s';
	public function __construct($driver, $name, $host, $user, $pass) {
		$this->driver = $driver;
		$this->name = $name;
		$this->host = $host;
		$this->user = $user;
		$this->pass = $pass;

		$dsn = $this->create_dsn();

		$this->spawn($dsn);
	}

	public function query($query) {
		$time = microtime(true);

		$sth = parent::query($query);

		self::addTime($time);
		self::$queries++;

		return $sth;
	}

	private function create_dsn() {
		if ($this->driver === 'mysql')
			return 'mysql:dbname='.$this->name.';host='.$this->host;

		if ($this->driver === 'sqlite')
			return 'sqlite:'.$this->name;
	}

	/**
	 * Call PDO's __construct() method and return the resulting PDO object
	 */
	private function spawn($dsn) {
		return parent::__construct($dsn, $this->user, $this->pass, array(
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_STATEMENT_CLASS => array('DBStatement', array($this)),
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
		));
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
