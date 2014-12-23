<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit\Config;

/**
 * Interface for configuration service. Error handling is left up to the
 * implementation.
 *
 * This interface is enterprise quality.
 */
interface ConfigServiceInterface {
    /**
     * Registers a pool identifier (or pool name) and associates it with a
     * dictionary. The dictionary shall not have to be registered at the time
     * this method is called.
     *
     * @param string $poolName Name of pool
     * @param string $dictName Name of associated dictionary
     */
    public function addPool($poolName, $dictName);

    /**
     * Retrieves a pool.
     *
     * @param string $poolName
     * @param array $poolArgs
     *
     * @return PoolInterface
     */
    public function getPool($poolName, array $poolArgs = []);

    /**
     * Registers a dictionary loader.
     *
     * @param DictionaryLoaderInterface $loader
     */
    public function addDictionaryLoader(DictionaryLoaderInterface $loader);

    /**
     * Retrieves a dictionary by its name.
     *
     * This method must be able to retrieve dictionaries from the dictionary
     * loaders, querying every loader in the reverse order they were added.
     *
     * @param string $dictName Name of dictionary.
     *
     * @return Dictionary
     */
    public function getDictionary($dictName);

    /**
     * Retrives a dictionary by the name of the pool.
     *
     * @param string $poolName
     *
     * @return Dictionary
     */
    public function getDictionaryByPoolName($poolName);
}
