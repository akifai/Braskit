<?php
/*
 * Copyright (C) 2013-2015 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit\Config;

/**
 * Interface for config dictionaries.
 *
 * @todo Complete this.
 */
interface DictionaryInterface {
    /**
     * Adds a key to the dictionary.
     *
     * @param $key string       The key.
     * @param $parameters array Its options.
     */
    public function add($key, array $parameters);

    /**
     * Retrieves the default value for the key.
     *
     * @return mixed The value.
     */
    public function getDefault($key);

    /**
     * Retrieves the type for an option.
     */
    public function getType($key);

    /**
     * @return array
     */
    public function getKeys();
}
