<?php
/*
 * Copyright (C) 2013-2015 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit\Config;

class Option implements OptionInterface {
    /**
     * Option key.
     *
     * @var string
     */
    protected $key;

    /**
     * Option type.
     *
     * @var string
     */
    protected $type;

    /**
     * Default option value.
     *
     * @var mixed
     */
    protected $default;

    /**
     * Current value.
     *
     * @var mixed
     */
    protected $value;

    /**
     * Whether or not option is modified.
     *
     * @var boolean
     */
    protected $modified;

    /**
     * Constructor.
     *
     * @param string $key
     * @param DictionaryInterface $dict
     * @param PoolInterface $pool
     */
    public function __construct(
        $key,
        DictionaryInterface $dict,
        PoolInterface $pool
    ) {
        $this->key = $key;
        $this->dict = $dict;
        $this->pool = $pool;
    }

    /**
     * {@inheritdoc}
     */
    public function getKey() {
        return $this->key;
    }

    /**
     * {@inheritdoc}
     */
    public function getType() {
        if (!isset($this->type)) {
            $this->type = $this->dict->getType($this->key);
        }

        return $this->type;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefault() {
        if (!isset($this->default)) {
            $this->default = $this->dict->getDefault($this->key);
        }

        return $this->default;
    }

    /**
     * {@inheritdoc}
     */
    public function getValue() {
        if (!isset($this->value)) {
            $this->value = $this->pool->get($this->key);
        }

        return $this->value;
    }

    /**
     * {@inheritdoc}
     */
    public function isModified() {
        if (!isset($this->modified)) {
            $this->modified = $this->pool->isModified($this->key);
        }

        return $this->modified;
    }
}
