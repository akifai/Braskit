<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit\User;

use Braskit\Error;
use Braskit\User;
use PgError; // todo

class Create extends User {
    protected $self_level = 0;

    public function __construct($username, $self_level = false) {
        $this->username = $username;
        $this->self_level = $self_level === false ? 9999 : $self_level;
    }

    public function commit() {
        global $app;

        try {
            $app['db']->insertUser($this);
        } catch (\PDOException $e) {
            $err = $e->getCode();

            switch ($err) {
            case PgError::UNIQUE_VIOLATION:
                // Username collision
                throw new Error("A user with that name already exists.");
                break;
            default:
                // Unknown error
                throw $e;
            }
        }
    }
}

/* vim: set ts=4 sw=4 sts=4 et: */
