<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit;

interface Cache {
    public function get($key);
    public function set($key, $value, $ttl = false);
    public function delete($key);
    public function purge();
}

/* vim: set ts=4 sw=4 sts=4 et: */
