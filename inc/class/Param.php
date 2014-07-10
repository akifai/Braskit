<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit;

use \RuntimeException;
use \Request;

class Param {
    const T_STRING = 1; // can be string
    const T_ARRAY = 2; // can be array
    const TYPE_FLAGS = 3; // string|array
    const DEFAULT_TYPE_FLAGS = 1; // string

    const M_GET = 4; // can be GET value
    const M_POST = 8; // can be POST value
    const M_COOKIE = 16; // can be cookie value
    const METHOD_FLAGS = 28; // get|post|cookie
    const DEFAULT_METHOD_FLAGS = 12; // get|post

    const S_STRICT = 32; // return false if parameter is missing
    const S_DEFAULT = 13; // string|get|post

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var integer
     */
    protected $flags = self::S_DEFAULT;

    public function __construct(Request $request) {
        $this->request = $request;
    }

    /**
     * Set the flags to use for flagless get()
     */
    public function flags($flags) {
        if (is_string($flags)) {
            $flags = $this->parseStringFlags($flags);
        } else {
            $this->checkFlags($flags);
        }

        $this->flags = $flags;

        // so the caller can get a Param object and set its flags simultaneously
        return $this;
    }

    public function get($name, $flags = false) {
        if ($flags === false) {
            // use the currently set flags
            $flags = $this->flags;
        } elseif (is_string($flags)) {
            $flags = $this->parseStringFlags($flags);
        } else {
            $this->checkFlags($flags);
        }

        $request = $this->request;

        // set the default value
        if ($flags & self::S_STRICT) {
            // strict mode - boolean false indicates no value
            $default = false;
        } elseif ($flags & self::T_STRING) {
            // string has precedence over array
            $default = '';
        } elseif ($flags & self::T_ARRAY) {
            $default = array();
        }

        // find the parameter value in the request data
        if (($flags & self::M_POST) && isset($request->post[$name])) {
            // POST values
            $value = $request->post[$name];
        } elseif (($flags & self::M_GET) && isset($request->get[$name])) {
            // GET values
            $value = $request->get[$name];
        } elseif (($flags & self::M_COOKIE) && isset($request->cookie[$name])) {
            // COOKIE values
            $value = $request->cookie[$name];
        } else {
            // no parameter found
            return $default;
        }

        // return defined string
        if (($flags & self::T_STRING) && is_string($value)) {
            return $value;
        }

        // return defined array
        if (($flags & self::T_ARRAY) && is_array($value)) {
            return $value;
        }

        // type mismatch
        return $default;
    }

    /**
     * Parses flags in a string.
     *
     * @param string $flags
     * @return integer
     */
    public function parseStringFlags($flags) {
        $parts = preg_split('/\s+/', $flags, -1, PREG_SPLIT_NO_EMPTY);
        $newflags = 0;

        foreach ($parts as $part) {
            switch ($part) {
            case 'string':
                $newflags |= self::T_STRING;
                break;
            case 'array':
                $newflags |= self::T_ARRAY;
                break;
            case 'get':
                $newflags |= self::M_GET;
                break;
            case 'post':
                $newflags |= self::M_POST;
                break;
            case 'cookie':
                $newflags |= self::M_COOKIE;
                break;
            case 'strict':
                $newflags |= self::S_STRICT;
                break;
            case 'default':
                $newflags |= self::S_DEFAULT;
                break;
            default:
                throw new RuntimeException("Param: Invalid flag '$part'.");
            }
        }

        if (!($newflags & self::TYPE_FLAGS)) {
            // inherit default type flags if no type flags are set
            $newflags |= self::DEFAULT_TYPE_FLAGS;
        }

        if (!($newflags & self::METHOD_FLAGS)) {
            // inherit default method flags if no methods are set
            $newflags |= self::DEFAULT_METHOD_FLAGS;
        }

        $this->checkFlags($newflags);

        return $newflags;
    }

    /**
     * Check a flag value.
     *
     * @param integer $flags
     * @throws RuntimeException if the flags are invalid
     */
    protected function checkFlags($flags) {
        if (!$flags) {
            // no flags
            throw new RuntimeException('Param: No flags provided.');
        }

        if (!($flags & self::TYPE_FLAGS)) {
            // missing type flag(s)
            throw new RuntimeException('Param: No type flag provided.');
        }

        if (!($flags & self::METHOD_FLAGS)) {
            // missing method flag(s)
            throw new RuntimeException('Param: No method flag provided.');
        }
    }
}

/* vim: set ts=4 sw=4 sts=4 et: */
