<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit\Config;

use Braskit\Config;

class Board extends Config {
    protected $standard_config = 'board_config.php';

    public function __construct($board) {
        $this->cache_key = $board.'_config';
        $this->db_key = (string)$board;

        parent::__construct();
    }
}

/* vim: set ts=4 sw=4 sts=4 et: */
