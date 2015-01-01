<?php
/*
 * Copyright (C) 2013-2015 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit\Cache;

/**
 * A cache implementation that doesn't actually cache anything. Should only be
 * used for testing and debugging.
 */
class NullCache implements CacheInterface {
    /**
     * {@inheritdoc}
     */
    public function has($key) {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function get($key) {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value, $ttl = null) {
        if ($value === null) {
            throw new \InvalidArgumentException('Cached value cannot be NULL');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key) {
        // nothing to do
    }

    /**
     * {@inheritdoc}
     */
    public function purge() {
        // nothing to do
    }
}
