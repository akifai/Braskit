<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit\User;

use Braskit\Database;
use Braskit\PgError;
use Braskit\User;

class UserEditCommitAction implements UserCommitActionInterface {
    protected $db;

    public function __construct(Database $db) {
        $this->db = $db;
    }

    public function commit(User $user, User $committer = null) {
        if (!$user->hasChanges()) {
            // nothing to do
            return;
        }

        try {
            $this->db->modifyUser($user);
        } catch (\PDOException $e) {
            $err = $e->getCode();

            switch ($err) {
            case PgError::UNIQUE_VIOLATION:
                // Username collision
                throw new Error("A user with that name already exists.");

            default:
                // Unknown error
                throw $e;
            }
        }
    }
}
