<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit\Cache;

use Braskit\Cache;

class Debug implements Cache {
    public function get($key) {
        return false;
    }

    public function set($key, $value, $ttl = false) {
        return false;
    }

    public function delete($key) {
        return true;
    }

    public function purge() {
        return true;
    }
}

/* vim: set ts=4 sw=4 sts=4 et: */
