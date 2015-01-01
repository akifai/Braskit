<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit\Cache;

/**
 * Cache using APC.
 */
class APCCache implements CacheInterface {
    /**
     * {@inheritdoc}
     */
    public function has($key) {
        return apc_exists($key);
    }

    /**
     * {@inheritdoc}
     */
    public function get($key) {
        $value = apc_fetch($key, $success);

        return $success ? $value : null;
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value, $ttl = null) {
        if ($value === null) {
            throw new \InvalidArgumentException('Cached value cannot be NULL');
        }

        apc_store($key, $value, (int)$ttl);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key) {
        return apc_delete($key);
    }

    /**
     * {@inheritdoc}
     *
     * @todo shared cache, etc
     */
    public function purge() {
        return apc_clear_cache('user');
    }
}
