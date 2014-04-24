<?php

class DBConnection extends PDO {
	public $time = 0;
	public $queries = 0;

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

		if ($this->dsn) {
			$this->dsn = $dsn;
		} else {
			$this->dsn = $this->createDSN();
		}

		$this->spawn();
	}

	public function query($query) {
		$time = microtime(true);

		$sth = parent::query($query);

		// update the timer/counter
		$this->time += microtime(true) - $time;
		$this->queries++;

		return $sth;
	}

	protected function createDSN() {
		$dsn = 'pgsql:dbname='.$this->name;

		if ($this->host === (string)$this->host && $this->host) {
			$dsn .= ';host='.$this->host;
		}

		return $dsn;
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
}

class DBStatement extends PDOStatement {
	protected $dbh;

	protected function __construct($dbh) {
		$this->dbh = $dbh;
	}

	public function execute($params = null) {
		$time = microtime(true);

		$sth = parent::execute($params);

		// update the timer/counter
		$this->dbh->time += microtime(true) - $time;
		$this->dbh->queries++;

		return $sth;
	}
}
