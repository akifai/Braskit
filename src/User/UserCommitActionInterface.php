<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit\User;

use Braskit\User;

/**
 * Interface for defining a strategy for User::commit().
 */
interface UserCommitActionInterface {
    /**
     * @param User $user 
     * @param User|null $committer User performing the commit.
     */
    public function commit(User $user, User $committer = null);
}
