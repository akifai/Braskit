<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit;

class Session implements \ArrayAccess, \Iterator {
    public function __construct($session_name) {
        session_name($session_name);
        session_start();
    }

    public function clean() {
        $_SESSION = array();
    }

    public function getName() {
        return session_name();
    }

    public function getID() {
        return session_id();
    }

    //
    // Iterator methods
    //

    public function current() {
        return current($_SESSION);
    }

    public function key() {
        return key($_SESSION);
    }

    public function next() {
        next($_SESSION);
    }

    public function rewind() {
        reset($_SESSION);
    }

    public function valid() {
        return isset($_SESSION[key($_SESSION)]);
    }

    //
    // ArrayAccess methods
    //

    public function offsetExists($offset) {
        return isset($_SESSION[$offset]);
    }

    public function offsetGet($offset) {
        if (isset($_SESSION[$offset])) {
            return $_SESSION[$offset];
        }

        return null;
    }

    public function offsetSet($offset, $value) {
        $_SESSION[$offset] = $value;
    }

    public function offsetUnset($offset) {
        unset($_SESSION[$offset]);
    }
}

/* vim: set ts=4 sw=4 sts=4 et: */
