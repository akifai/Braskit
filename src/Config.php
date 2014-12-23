<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit;

/**
 * Model for entries in config table.
 */
class Config {
    /**
     * The pool identifier.
     *
     * @var string
     */
    public $pool;

    /**
     * Arguments for the pool identifier.
     *
     * @var array
     */
    public $args;

    /**
     * The configuration key.
     */
    public $key;

    /**
     * The configuration value.
     *
     * @var scalar|array
     */
    public $value;

    /**
     * Constructor. The only thing this does is decode the json args and option
     * value.
     */
    public function __construct() {
        if (is_string($this->args)) {
            $this->args = json_decode($this->args);
        }

        if (is_string($this->value)) {
            $this->value = json_decode($this->value);
        }
    }
}
