<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit;

use Braskit\Database;
use Braskit\User;
use Braskit\User\UserCreateCommitAction;
use Braskit\User\UserEditCommitAction;

/**
 * Service for performing operations on users (e.g. User objects).
 */
class UserService {
    protected $db;

    public function __construct(Database $db) {
        $this->db = $db;
    }

    /**
     * Retrieve a user by its username.
     *
     * @param string $username The username.
     * @param boolean $throw   Whether to throw exceptions or not.
     *
     * @return User|null
     */
    public function get($username, $throw = true) {
        $user = $this->db->getUser($username);

        if ($user) {
            return $user;
        }

        if ($throw) {
            throw new Error('No such user account.');
        }

        return null;
    }

    /**
     * Retrieve every user.
     *
     * @return array
     */
    public function getAll() {
        return $this->db->getUserList();
    }

    /**
     * Create a new user. This method returns a User object which can be
     * manipulated and committed to the database using $user->commit().
     *
     * @param User $creator The user performing the action.
     *
     * @return User
     */
    public function create(User $creator) {
        $user = new User();

        $user->setCommitter($creator);
        $user->setCommitAction(new UserCreateCommitAction($this->db));

        return $user;
    }

    /**
     * Edit a user. This method returns a User object which can be manipulated
     * and committed to the database using $user->commit().
     *
     * @param User|string $subject The user to edit.
     * @param User $editor         The user performing the action.
     *
     * @return User
     */
    public function edit($subject, User $editor) {
        if (!($subject instanceof User)) {
            $user = $this->get($subject);
        }

        $user->setCommitter($editor);
        $user->setCommitAction(new UserEditCommitAction($this->db));

        return $user;
    }

    /**
     * Deletes a user account.
     *
     * @param User|string $subject The user to delete.
     * @param User $deleter        The user performing the action.
     */
    public function delete($subject, User $deleter) {
        if (!($subject instanceof User)) {
            $user = $this->get($subject);
        }

        $this->db->deleteUser($subject->username);
    }
}
