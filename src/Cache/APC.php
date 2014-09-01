<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit\Cache;

use Braskit\Cache;

class APC implements Cache {
    public function get($key) {
        return apc_fetch($key);
    }

    public function set($key, $value, $ttl = false) {
        return apc_add($key, $value, $ttl);
    }

    public function delete($key) {
        return apc_delete($key);
    }

    /**
     * @todo shared cache, etc
     */
    public function purge() {
        return apc_clear_cache('user');
    }
}

/* vim: set ts=4 sw=4 sts=4 et: */
