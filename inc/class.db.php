<?php
defined('TINYIB_BOARD') or exit;

class TinyIB_DB extends PDO {
	public static $time = 0;
	public static $queries = 0;

	const DSN_FORMAT = '%s:dbname=%s;host=%s';
	public function __construct() {
		$dsn = sprintf(self::DSN_FORMAT,
			TINYIB_DBMODE, TINYIB_DBNAME, TINYIB_DBHOST);

		try {
			$this->spawn($dsn);
		} catch (PDOException $e) {
			fancyDie($e->getMessage());
		}
	}

	public function query($query) {
		$time = microtime(true);

		try {
			$sth = parent::query($query);
		} catch (PDOException $e) {
			fancyDie($e->getMessage());
		}

		self::addTime($time);
		self::$queries++;

		return $sth;
	}

	/**
	 * Call PDO's __construct() method and return the resulting PDO object
	 */
	private function spawn($dsn) {
		return parent::__construct($dsn, TINYIB_DBUSERNAME, TINYIB_DBPASSWORD, array(
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_STATEMENT_CLASS => array('TinyIB_DBStatement', array($this)),
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
		));
	}

	public static function addTime($time) {
		self::$time += microtime(true) - $time;
	}
}

class TinyIB_DBStatement extends PDOStatement {
	protected $dbh;
	protected function __construct($dbh) {
		$this->dbh = $dbh;
	}

	public function execute($params = null) {
		$time = microtime(true);
		try {
			$sth = parent::execute($params);
		} catch (PDOException $e) {
			fancyDie($e->getMessage());
		}

		TinyIB_DB::addTime($time);
		TinyIB_DB::$queries++;

		return $sth;
	}
}
