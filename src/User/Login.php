<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit\User;

use Braskit\Error;
use Braskit\User;

class Login extends User {
    public function __construct($username, $password) {
        try {
            $this->load($username);
        } catch (Error $e) {
            throw new Error('Invalid login.');
        }

        if (!$this->checkPassword($password)) {
            throw new Error('Invalid login.');
        }

        $this->checkSuspension();
    }

    public function __wakeup() {
        $hash = $this->password;

        // Things might change between requests. Reload everything.
        $this->load($this->username);

        // Just in case...
        // remember, $this->load() replaces $this->password, so $hash
        // and $this->password aren't necessarily equal
        if (!$hash || !$this->password) {
            throw new \RuntimeException('Cannot restore user session.');
        }

        // Validate session password with database password
        if ($hash !== $this->password) {
            throw new Error('Invalid login.');
        }

        $this->checkSuspension();
    }
}

/* vim: set ts=4 sw=4 sts=4 et: */
