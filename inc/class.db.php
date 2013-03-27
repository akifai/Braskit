<?php
defined('TINYIB') or exit;

class Database extends PDO {
	public static $time = 0;
	public static $queries = 0;

	const DSN_FORMAT = '%s:dbname=%s;host=%s';
	public function __construct() {
		$dsn = self::create_dsn();

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
		if (TINYIB_DBMODE === 'mysql')
			return 'mysql:dbname='.TINYIB_DBNAME.';host='.TINYIB_DBHOST;

		if (TINYIB_DBMODE === 'sqlite')
			return 'sqlite:'.TINYIB_DBNAME;
	}

	/**
	 * Call PDO's __construct() method and return the resulting PDO object
	 */
	private function spawn($dsn) {
		return parent::__construct($dsn, TINYIB_DBUSERNAME, TINYIB_DBPASSWORD, array(
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
