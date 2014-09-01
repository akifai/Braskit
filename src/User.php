<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit;

/*
 * Usage:
 *
 *   // Check login
 *   $user = new Braskit\User\Login($username, $password);
 *
 *   // Create user
 *   $newUser = $user->create("username");
 *   $newUser->setPassword("password");
 *   $newUser->setLevel(9999);
 *   $newUser->commit();
 *
 *   // Edit user
 *   $target = $user->edit("username");
 *   $target->setUsername("new_username");
 *   $target->setPassword("password");
 *   $target->setLevel(9999);
 *   $target->commit();
 *
 *   // Delete user
 *   $user->delete("username");
 *
 * TODO:
 *   - Check permissions for actions
 *   - Test everything properly
 */

class User {
    protected $changes = array();

    public $username = false;
    public $password = false;
    public $lastlogin = 0;
    public $level = 0;
    public $email = '';
    public $capcode = '';

    public function __toString() {
        return $this->username;
    }

    public function create($username) {
        return new User\Create($username, $this->level);
    }

    public function edit($id) {
        return new User\Edit($id, $this->level);
    }

    public function delete($username) {
        global $app;

        if ($this->username === $username)
            throw new Error('You cannot delete yourself.');

        // TODO: Check if we have higher permissions than the user
        // we're deleting.

        $app['db']->deleteUser($username);
    }


    //
    // Modifiers
    //

    public function setEmail($email) {
        if (!strlen($email) || $email === $this->email)
            return;

        if ($email && filter_var($email, FILTER_VALIDATE_EMAIL) === false)
            throw new Error('Invalid email address.');

        $this->email = $email;
        $this->changes[] = 'email';
    }

    public function setCapcode($capcode) {
        if (!strlen($capcode) || $capcode === $this->capcode)
            return;

        // TODO: Restrict to a subset of HTML

        $this->capcode = $capcode;
        $this->changes[] = 'capcode';
    }

    public function setLevel($level) {
        if ($level == $this->level)
            return;

        $this->level = (int)$level;
        $this->changes[] = 'level';
    }

    public function setPassword($password) {
        if (!strlen($password)) {
            return;
        }

        $this->password = password_hash($password, PASSWORD_DEFAULT);
        $this->changes[] = 'password';
    }

    public function commit() {
        global $app;

        if (!$this->changes) {
            return; // nothing to do
        }

        try {
            $app['db']->modifyUser($this);
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


    //
    // Internals
    //

    protected function requireLevel($level) {
        if ($this->level >= $level)
            return;

        throw new Error("You don't have sufficient permissions.");
    }

    protected function checkSuspension() {
        if ($this->level < 1) {
            throw new Error('User account is suspended.');
        }
    }

    /**
     * Loads a user account by its username.
     *
     * @param string Username
     */
    protected function load($username) {
        global $app;

        $row = $app['db']->getUser($username);

        if ($row === false)
            throw new Error("No such user exists.");

        $this->username = $row->username;
        $this->password = $row->password;
        $this->lastlogin = $row->lastlogin;
        $this->level = $row->level;
        $this->email = $row->email;
        $this->capcode = $row->capcode;
    }


    //
    // Cryptography
    //

    protected function checkPassword($password) {
        $hash = $this->password;

        if (!password_verify($password, $hash)) {
            return false;
        }

        if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
            $this->setPassword($password);
            $this->commit();
        }

        return true;
    }


    //
    // Static API
    //

    public static function get($username) {
        global $app;

        return $app['db']->getUser($username);
    }

    public static function getAll() {
        global $app;

        return $app['db']->getUserList();
    }
}

/* vim: set ts=4 sw=4 sts=4 et: */
