<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit\User;

use Braskit\User;

class Admin extends User {
    public function __construct() {}

    // we always have the highest permissions
    public $level = 9999;

    // doesn't throw an exception, so it bypasses all the checks
    protected function requireLevel($level) {}
}

/* vim: set ts=4 sw=4 sts=4 et: */
