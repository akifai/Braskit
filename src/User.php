<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit;

use Braskit\User\UserCommitActionInterface;

/**
 * Representation of a user.
 */
class User {
    /**
     * @var string
     */
    public $username;

    /**
     * @var string
     */
    public $password;

    /**
     * @var null|int
     */
    public $lastlogin = null;

    /**
     * @var int
     */
    public $level = 0;

    /**
     * @var string
     */
    public $email = '';

    /**
     * @var string
     */
    public $capcode = '';

    /**
     * Represents the current username in the database. This property is
     * modified by setUsername() if the username changes.
     *
     * @var string
     */
    protected $id;

    /**
     * An associative array where the array keys indicate which of the user's
     * properties have changed.
     *
     * @var array
     */
    protected $changes = [];

    /**
     * @var UserCommitActionInterface
     */
    protected $commitAction;

    /**
     * @var User|null
     */
    protected $committer = null;

    /**
     * @deprecated
     */
    public function __toString() {
        return $this->username;
    }


    //
    // Getters
    //

    /**
     * Retrieves the current database key for this user. Must be used in the
     * WHERE clause when changing a username.
     *
     * @return string
     */
    public function getID() {
        return is_null($this->id) ? $this->username : $this->id;
    }


    //
    // Modifiers
    //

    /**
     * Sets a new username.
     *
     * @param string $username
     *
     * @return self
     */
    public function setUsername($username) {
        if (!strlen($username) || $username === $this->username) {
            return;
        }

        if (!preg_match('/^\w+$/', $username)) {
            throw new Error('Invalid username.');
        }

        if ($this->id === null) {
            // set the id to the old username
            $this->id = $this->username;
        }

        $this->username = $username;

        $this->changes['username'] = null;

        return $this;
    }

    /*
     * Sets a new email address.
     *
     * @param string $email
     *
     * @return self
     */
    public function setEmail($email) {
        if ($email === $this->email) {
            return;
        }

        if ($email && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new Error('Invalid email address');
        }

        $this->email = $email;
        $this->changes['email'] = null;

        return $this;
    }

    /**
     * Sets a new capcode.
     *
     * @param string $capcode
     *
     * @return self
     *
     * @todo Restrict to a subset of HTML.
     */
    public function setCapcode($capcode) {
        if ($capcode === $this->capcode) {
            return;
        }

        $this->capcode = $capcode;
        $this->changes['capcode'] = null;

        return $this;
    }

    /**
     * Sets a new user level.
     *
     * @param int $level
     *
     * @return self
     */
    public function setLevel($level) {
        if ($level == $this->level) {
            return;
        }

        if ($level < 0 || $level > 9999) {
            throw new Error('Level must be between 0 and 9999');
        }

        $this->level = (int)$level;
        $this->changes['level'] = null;

        return $this;
    }

    /**
     * Sets a new password.
     *
     * @param string $password New password in plain text.
     *
     * @return self
     *
     * @todo Check password strength
     */
    public function setPassword($password) {
        if (!strlen($password)) {
            return;
        }

        $this->password = password_hash($password, PASSWORD_DEFAULT);
        $this->changes['password'] = null;

        return $this;
    }


    //
    // Committing stuff
    //

    /**
     * Execute the commit action.
     *
     * @throws Error if no username has been set
     *
     * @return mixed The output of the commit action object's commit() method.
     */
    public function commit() {
        if ($this->committer) {
            // the committer must have permission
            $this->committer->checkLevel($this);
        }

        if (!isset($this->username)) {
            throw new Error('No username has been set');
        }

        if ($this->commitAction instanceof UserCommitActionInterface) {
            return $this->commitAction->commit($this, $this->committer);
        }

        throw new \RuntimeException('No commit action has been specified');
    }

    /**
     * Set the user committing an action.
     */
    public function setCommitter(User $committer) {
        $this->committer = $committer;
    }

    /**
     * Set a commit action object.
     *
     * @param UserCommitActionInterface $action
     */
    public function setCommitAction(UserCommitActionInterface $action) {
        $this->commitAction = $action;
    }

    /**
     * @return boolean Whether or not changes have been made.
     */
    public function hasChanges() {
        return (bool)$this->changes;
    }

    /**
     * Compares this user's level against another user's.
     *
     * @param User $user Other user.
     *
     * @throws Error if the other user has a lower level.
     */
    public function checkLevel(User $user) {
        if ($this->level >= $user->level) {
            return;
        }

        throw new Error("You don't have sufficient permissions");
    }

    /**
     * Check if the user account is suspended.
     *
     * @throws Error if the user account is suspended.
     */
    public function checkSuspension() {
        if ($this->level < 1) {
            throw new Error('User account is suspended');
        }
    }

    /**
     * Checks the password.
     *
     * @param string $password Plain text password.
     *
     * @throws Error if the password didn't match
     */
    public function checkPassword($password) {
        $hash = $this->password;

        if (!password_verify($password, $hash)) {
            throw new Error('Incorrect password');
        }

        if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
            $this->setPassword($password);
        }
    }
}
