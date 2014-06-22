<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See the LICENSE file for terms and conditions of use.
 */

namespace Braskit;

use PDOStatement;

class Database_Statement extends PDOStatement {
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

/* vim: set ts=4 sw=4 sts=4 et: */
