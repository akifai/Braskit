<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit\Config;

use Pimple\Container;

/**
 * Allows lazy loading of dictionaries using a Pimple container.
 */
class PimpleAwareDictionaryLoader implements DictionaryLoaderInterface {
    /**
     * Assoc array of dictionary name => corresponding identifier in Pimple.
     *
     * @var array
     */
    protected $dictionaries = [];

    /**
     * @var Container
     */
    protected $container;

    /**
     * Constructor.
     *
     * @param Container $container Instance of Pimple.
     */
    public function __construct(Container $container) {
        $this->container = $container;
    }

    /**
     * Sets the name of a dictionary and its corresponding identifier in Pimple.
     *
     * @param string $name Dictionary name.
     * @param string $dependency Corresponding identifier in Pimple.
     */
    public function addDictionary($name, $dependency) {
        $this->dictionaries[$name] = $dependency;
    }

    /**
     * {@inheritdoc}
     */
    public function hasDictionary($dict) {
        return isset($this->dictionaries[$dict]);
    }

    /**
     * {@inheritdoc}
     */
    public function getDictionary($dict) {
        if (!isset($this->dictionaries[$dict])) {
            throw new \InvalidArgumentException("Unknown dictionary: '$dict'");
        }

        // no need to check the key with pimple, since it spits out exceptions
        return $this->container[$this->dictionaries[$dict]];
    }
}
