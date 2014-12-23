<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit\Config;

use Braskit\Cache;
use Braskit\Database;

class ConfigService implements ConfigServiceInterface {
    /**
     * @var Cache
     */
    public $cache;

    /**
     * @var Database
     */
    public $db;

    /**
     * Array of dictionary loaders.
     *
     * @var array
     */
    protected $dictionaryLoaders = [];

    /**
     * Constructor.
     *
     * @var Cache $cache 
     * @var Database $db
     */
    public function __construct(Cache $cache, Database $db) {
        $this->cache = $cache;
        $this->db = $db;
    }

    /**
     * {@inheritdoc}
     */
    public function addPool($poolName, $dictName) {
        if (isset($this->pools[$poolName])) {
            // the pool has already been defined
            $msg = "Pool '$poolName' cannot be re-registered";
            throw new \InvalidArgumentException($msg);
        }

        $argc = substr_count($poolName, '%');

        $this->pools[$poolName] = [
            'arguments' => $argc,
            'dictionary' => $dictName,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getPool($poolName, array $poolArgs = []) {
        if (!isset($this->pools[$poolName])) {
            throw new \InvalidArgumentException("Invalid pool");
        }

        $ac = count($poolArgs); // number of given args
        $pc = $this->pools[$poolName]['arguments']; // number of required args

        if ($ac !== $pc) {
            $msg = "The pool requires $pc arguments, but only $ac were given";
            throw new \InvalidArgumentException($msg);
        }

        return new Pool($poolName, $poolArgs, $this);
    }

    /**
     * {@inheritdoc}
     */
    public function addDictionaryLoader(DictionaryLoaderInterface $loader) {
        array_unshift($this->dictionaryLoaders, $loader);
    }

    /**
     * {@inheritdoc}
     */
    public function getDictionary($dictName) {
        foreach ($this->dictionaryLoaders as $loader) {
            if ($loader->hasDictionary($dictName)) {
                return $loader->getDictionary($dictName);
            }
        }

        throw new \RuntimeException("No dictionary '$dictName' exists");
    }

    /**
     * {@inheritdoc}
     */
    public function getDictionaryByPoolName($poolName) {
        if (!isset($this->pools[$poolName])) {
            throw new \InvalidArgumentException("Invalid pool");
        }

        return $this->getDictionary($this->pools[$poolName]['dictionary']);
    }
}
