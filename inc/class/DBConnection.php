<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

class DBConnection extends PDO {
	protected $counter;

	protected $name = '';
	protected $host = '';
	protected $user = '';
	protected $pass = '';
	protected $dsn = '';

	public function __construct($name, $host, $user, $pass, $counter, $dsn = '') {
		$this->name = $name;
		$this->host = $host;
		$this->user = $user;
		$this->pass = $pass;

		$this->counter = $counter;

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
		$this->counter->dbTime += microtime(true) - $time;
		$this->counter->dbQueries++;

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
		$options[PDO::ATTR_STATEMENT_CLASS] = array('DBStatement',
			array($this, $this->counter)
		);

		// return associative arrays when fetch()ing
		$options[PDO::ATTR_DEFAULT_FETCH_MODE] = PDO::FETCH_ASSOC;

		// use real prepared statements
		$options[PDO::ATTR_EMULATE_PREPARES] = false;

		return parent::__construct(
			$this->dsn,
			$this->user,
			$this->pass,
			$options
		);
	}
}

class DBStatement extends PDOStatement {
	protected $counter;
	protected $dbh;

	protected function __construct($dbh, $counter) {
		$this->dbh = $dbh;
		$this->counter = $counter;
	}

	public function execute($params = null) {
		$time = microtime(true);

		$sth = parent::execute($params);

		// update the timer/counter
		$this->counter->dbTime += microtime(true) - $time;
		$this->counter->dbQueries++;

		return $sth;
	}
}
