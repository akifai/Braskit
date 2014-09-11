<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit;

use Braskit\Error\CSRF as CSRFError;
use Symfony\Component\HttpFoundation\Session\Session;

class CSRF {
    const PARAM_KEY = 'csrf';
    const SESSION_KEY = 'csrf_token';

    protected $param;
    protected $session;

    protected $oldToken = false;

    public function __construct(Param $param, Session $session) {
        $this->param = $param;
        $this->session = $session;
    }

    /**
     * Checks if a CSRF token was sent and that it's valid.
     *
     * @throws CSRFError if the tokens did not match
     */
    public function check() {
        $success = $this->getToken() === $this->getTokenParam();

        if ($success) {
            $this->oldToken = $this->session->get(self::SESSION_KEY);

            $this->session->remove(self::SESSION_KEY);
        } else {
            throw new CSRFError('Invalid CSRF token.');
        }
    }

    /**
     * Set the CSRF token back to the previous one. Should be used in exception
     * handlers to prevent CSRF errors when resubmitting.
     */
    public function rollback() {
        if ($this->oldToken) {
            $this->session->set(self::SESSION_KEY, $this->oldToken);
            $this->oldToken = false;
        }
    }

    public function getToken() {
        if (!$this->session->has(self::SESSION_KEY)) {
            $this->session->set(self::SESSION_KEY, random_string(48));
        }

        return $this->session->get(self::SESSION_KEY);
    }

    public function getParamName() {
        return self::PARAM_KEY;
    }

    protected function getTokenParam() {
        return $this->param->get(self::PARAM_KEY, 'post');
    }
}

/* vim: set ts=4 sw=4 sts=4 et: */
