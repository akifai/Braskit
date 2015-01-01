<?php
/*
 * Copyright (C) 2013-2015 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit\Cache;

/**
 * Interface for key/value (object) storage.
 *
 * Inspired by PSR-6, however I've chosen to ignore the brain-damaged idea of
 * using "CacheItem" objects just to make NULL a supported value. Instead, raw
 * values are fetched or stored directly, while NULL indicates a cache miss and
 * is not a cacheable value. PSR-6 adapters will have to use some sort of silly
 * hack to make storing NULLs supported.
 */
interface CacheInterface {
    /**
     * Checks if an object is stored in cache.
     *
     * This check should not be considered reliable - implementations may or may
     * not check if the object has expired. Additionally, some implementations
     * are subject to race conditions between has() and get(). The solution is
     * to check if get() returns NULL.
     *
     * @param string $key Name of object to look up.
     *
     * @return boolean Whether or not an object is stored in cache.
     */
    public function has($key);

    /**
     * Retrieves an object, or NULL if it has expired or doesn't exist.
     *
     * @param string $key Object key.
     *
     * @return mixed|null 
     */
    public function get($key);

    /**
     * Stores an object in cache.
     *
     * @param string $key Object key.
     * @param mixed $value A serializable value of any kind, except NULL.
     * @param int $ttl Time in seconds until object expires.
     *
     * @throws \InvalidArgumentException if the value is NULL.
     */
    public function set($key, $value, $ttl = null);

    /**
     * Deletes an object.
     *
     * @param string $key Object key.
     */
    public function delete($key);

    /**
     * Purge the cache.
     */
    public function purge();
}
