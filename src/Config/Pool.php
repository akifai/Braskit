<?php
/*
 * Copyright (C) 2013-2015 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit\Config;

/**
 * Default config pool implementation.
 *
 * This implementation relies on the ConfigService to provide caching and
 * database services. It is also dictionary-agnostic when reading values--only
 * when modifying options in the pool does it load the dictionary.
 */
class Pool implements PoolInterface {
    /**
     * Pool name/identifier
     *
     * @var string
     */
    protected $name;

    /**
     * Pool arguments
     *
     * @var array
     */
    protected $args;

    /**
     * @var ConfigService
     */
    protected $service;

    /**
     * Whether the cache has been initialised or not.
     *
     * @var boolean
     */
    protected $initialised = false;

    /**
     * Cached key/value pairs for this pool.
     *
     * Structure:
     *
     * [
     *   'some_key' => [
     *     'value' => 'some value 1234567',
     *     'modified' => true,
     *   ],
     *   'some_other_value' => [
     *     'value' => 6.12345,
     *     'modified' => false,
     *   ],
     * ]
     *
     * @var array
     */
    protected $cache;

    /**
     * Key for retrieving/storing the pool's options in cache.
     *
     * @var string
     */
    protected $cacheKey;

    /**
     * Dictionary. Lazy-loaded, use $this->getDict() instead.
     *
     * @var Dictionary|null
     */
    protected $dict;

    /**
     * Constructor.
     *
     * @param string $name           The name of this pool.
     * @param array $args            The arguments for this pool instance.
     * @param ConfigService $service The configuration service.
     */
    public function __construct($name, array $args, ConfigService $service) {
        $this->name = $name;
        $this->args = $args;
        $this->service = $service;
    }

    /**
     * {@inheritdoc}
     */
    public function has($key) {
        if (!$this->initialised) {
            $this->initialiseCache();
        }

        return isset($this->cache[$key]);
    }

    /**
     * {@inheritdoc}
     */
    public function get($key) {
        if (!$this->initialised) {
            $this->initialiseCache();
        }

        if (!isset($this->cache[$key])) {
            throw new \InvalidArgumentException("No such key: '$key'");
        }

        return $this->cache[$key]['value'];
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value) {
        throw new \LogicException('Not implemented yet');
    }

    /**
     * {@inheritdoc}
     */
    public function reset($key) {
        throw new \LogicException('Not implemented yet');
    }

    /**
     * {@inheritdoc}
     */
    public function isModified($key) {
        if (!$this->initialised) {
            $this->initialiseCache();
        }

        if (!isset($this->cache[$key])) {
            throw new \InvalidArgumentException("No such key: '$key'");
        }

        return $this->cache[$key]['modified'];
    }

    /**
     * {@inheritdoc}
     */
    public function commit() {
        throw new \LogicException('Not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator() {
        return new PoolIterator($this->getDict(), $this);
    }

    /**
     * Sets up the cache so we can look up stuff fast.
     */
    protected function initialiseCache() {
        $cacheKey = $this->getCacheKey();

        // retrieve cache
        $this->cache = $this->service->cache->get($cacheKey);

        if (!is_array($this->cache)) {
            // no cache - we need to build it
            $this->cache = [];

            $dict = $this->getDict();

            // get the options in the db
            $db = $this->service->db->getPoolOptions($this->name, $this->args);

            foreach ($db as $option) {
                // set to value from db
                $this->cache[$option->key]['value'] = $option->value;

                // assume that if an option is stored in the database, its value
                // deviates from that of the dictionary's. this behaviour ought
                // to be changed, as the database cannot enforce that stored
                // values be non-default.
                $this->cache[$option->key]['modified'] = true;
            }

            // add remaining options using the dictionary
            foreach ($dict->getKeys() as $key) {
                if (!isset($this->cache[$key])) {
                    // set to default value
                    $this->cache[$key]['value'] = $dict->getDefault($key);

                    // if it's from the dictionary, it's unmodified
                    $this->cache[$key]['modified'] = false;
                }
            }

            // save the generated cache
            $this->service->cache->set($cacheKey, $this->cache);
        }

        // don't run this again
        $this->initialised = true;
    }

    /**
     * Creates a caching key for the pool.
     *
     * @return string The key
     */
    protected function getCacheKey() {
        if (!isset($this->cacheKey)) {
            // combine pool name and args into one array
            $args = array_merge([$this->name], $this->args);

            // urlencode encodes pipes, leaving the pipe character available as
            // a safe separator
            $key = sha1(implode('|', array_map('urlencode', $args)));

            $this->cacheKey = 'config_'.$key;
        }

        return $this->cacheKey;
    }

    /**
     * Retrieves the dictionary for the pool.
     *
     * @return Dictionary
     */
    protected function getDict() {
        if (!isset($this->dict)) {
            $this->dict = $this->service->getDictionaryByPoolName($this->name);
        }

        return $this->dict;
    }
}
