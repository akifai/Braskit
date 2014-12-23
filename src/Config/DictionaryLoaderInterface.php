<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit\Config;

/**
 * Interface for loading config dictionaries.
 */
interface DictionaryLoaderInterface {
    /**
     * Check whether a dictionary exists for this dictionary loader. 
     *
     * @param string $dictionary Name of dictionary.
     *
     * @return boolean If true, dictionary exists.
     */
    public function hasDictionary($name);

    /**
     * Return an instance of the request dictionary.
     *
     * @param string $dictionary Name of dictionary.
     *
     * @return Dictionary
     */
    public function getDictionary($name);
}
