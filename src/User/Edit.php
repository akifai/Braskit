<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit\User;

use Braskit\User;

class Edit extends User {
    protected $self_level = 0;

    public $newUsername = '';

    /// @todo check if we have permission to edit this user
    public function __construct($username, $self_level) {
        $this->load($username);

        $this->newUsername = $this->username;
    }

    public function setUsername($username) {
        if (!strlen($username)) {
            return;
        }

        $this->newUsername = $username;

        $this->changes[] = 'username';
    }
}

/* vim: set ts=4 sw=4 sts=4 et: */
