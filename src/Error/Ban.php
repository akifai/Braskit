<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit\Error;

use Braskit\Error;

class Ban extends Error implements \Iterator {
    public $ip = null;
    protected $bans = array();
    protected $pos = 0;

    public function setBans(Array $bans = array()) {
        for ($i = count($bans); $i--;) {
            if (!($bans[$i] instanceof Ban)) {
                throw new \InvalidArgumentException('Array items must be Ban objects');
            }
        }

        $this->bans = $bans;
    }

    public function current() {
        return $this->bans[$this->pos];
    }

    public function key() {
        return $this->pos;
    }

    public function next() {
        ++$this->pos;
    }

    public function rewind() {
        $this->pos = 0;
    }

    public function valid() {
        return isset($this->bans[$this->pos]);
    }
}

/* vim: set ts=4 sw=4 sts=4 et: */
