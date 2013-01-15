<?php
defined('TINYIB_BOARD') or exit;

class TinyIB_DB extends PDO {
	public static $time = 0;
	public static $queries = 0;

	const DSN_FORMAT = '%s:dbname=%s;host=%s';
	public function __construct() {
		$dsn = self::create_dsn();

		try {
			$this->spawn($dsn);
		} catch (PDOException $e) {
			make_error($e->getMessage());
		}
	}

	public function query($query) {
		$time = microtime(true);

		try {
			$sth = parent::query($query);
		} catch (PDOException $e) {
			make_error($e->getMessage());
		}

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
			make_error($e->getMessage());
		}

		TinyIB_DB::addTime($time);
		TinyIB_DB::$queries++;

		return $sth;
	}
}
