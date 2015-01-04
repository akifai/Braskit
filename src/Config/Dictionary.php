<?php
/*
 * Copyright (C) 2013-2015 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit\Config;

/**
 * @todo Complete this.
 */
class Dictionary implements DictionaryInterface {
    /**
     * List of valid keys.
     *
     * @var array
     */
    protected $keys = [];

    /**
     * {@inheritdoc}
     */
    public function add($key, array $parameters) {
        $this->keys[$key] = $parameters;
    }

    public function setType($key, $type) {
        throw new \LogicException('Not implemented yet');
    }

    public function setDefault($key, $value) {
        throw new \LogicException('Not implemented yet');
    }

    public function setModifier($key, callable $modifier) {
        throw new \LogicException('Not implemented yet');
    }

    /**
     * {@inheritdoc}
     */
    public function getDefault($key) {
        return $this->keys[$key]['default'];
    }

    public function getType($key) {
        return $this->keys[$key]['type'];
    }

    /**
     * {@inheritdoc}
     */
    public function getKeys() {
        return array_keys($this->keys);
    }
}
