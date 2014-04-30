<?php

class ParamException extends LogicException {}

/**
 * @todo Make this thing accept strings as flags. Using class constants makes
 *       calls to get()/flags() look horrendous.
 */
class Param {
    const T_STRING = 1; // can be string
    const T_ARRAY = 2; // can be array

    const M_GET = 4; // can be GET value
    const M_POST = 8; // can be POST value
    const M_COOKIE = 16; // can be cookie value
    const M_SERVER = 32; // can be server var

    const S_STRICT = 64; // returns false if parameter is missing
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
        $this->checkFlags($flags);

        $this->flags = $flags;

        // so the caller can get a Param object and set its flags simultaneously
        return $this;
    }

    public function get($name, $flags = false) {
        if ($flags === false) {
            // use the currently set flags
            $flags = $this->flags;
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
        } elseif (($flags & self::M_SERVER) && isset($request->server[$name])) {
            // Server variables
            $value = $request->server[$name];
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
     * Check a flag value.
     *
     * @param integer $flags
     * @throws ParamException if the flags are invalid
     */
    public static function checkFlags($flags) {
        if (!$flags) {
            // no flags
            throw new ParamException('No flags provided.');
        }

        if (!($flags & (self::T_STRING | self::T_ARRAY))) {
            // missing type flag(s)
            throw new ParamException("No type flag provided. $flags");
        }

        if (!($flags &
            (self::M_GET | self::M_POST | self::M_COOKIE | self::M_SERVER)
        )) {
            // missing method flag(s)
            throw new ParamException('No method flag provided.');
        }
    }
}

/* vim: set ts=4 sw=4 sts=4 et: */
