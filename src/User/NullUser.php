<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit\User;

use Braskit\User;

/**
 * Dummy user. Never suspended, all passwords match, has full permissions.
 */
class NullUser extends User {
    public function __construct() {
        $this->level = 9999;
    }

    public function checkLevel(User $user) {}
    public function checkPassword($password) {}
    public function checkSuspension() {}
    public function commit() {}
}
