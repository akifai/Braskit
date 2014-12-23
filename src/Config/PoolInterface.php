<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit\Config;

use Braskit\Error;

/**
 * Interface for config pools.
 */
interface PoolInterface extends \IteratorAggregate {
    /**
     * Checks that a key is valid for this pool.
     *
     * @return boolean
     */
    public function has($key);

    /**
     * Retrives an option's value.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function get($key);

    /**
     * Sets an option in the pool to a given value. Subsequent calls to get()
     * in the same object instance should return this value.
     *
     * @param string $key   Option key.
     * @param boolean|int|float|string|array $value  The desired value.
     */
    public function set($key, $value);

    /**
     * Resets a key to the default value.
     *
     * @param string $key Key to reset.
     */
    public function reset($key);

    /**
     * Checks if an option is modified.
     *
     * @return boolean
     */
    public function isModified($key);

    /**
     * Persist the pool.
     */
    public function commit();

    /**
     * Retrieve an Iterator instance for iterating over options in the pool
     *
     * @return \Iterator
     */
    public function getIterator();
}
