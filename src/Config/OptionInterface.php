<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit\Config;

/**
 * Interface for classes that retrieve information about options. Intended for
 * the pool iterator.
 */
interface OptionInterface {
    /**
     * Retrieves the default value for this option.
     *
     * @return mixed
     */
    public function getDefault();

    /**
     * Retrieves the key.
     *
     * @return string
     */
    public function getKey();

    /**
     * Retrieves the type.
     *
     * @return string
     */
    public function getType();

    /**
     * Retrieves the value.
     *
     * @return mixed
     */
    public function getValue();

    /**
     * Checks whether this option is modified from its default value.
     *
     * @return boolean
     */
    public function isModified();
}
