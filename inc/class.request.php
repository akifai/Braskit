<?php

class Request {
    public $get = array();
    public $post = array();
    public $cookie = array();
    public $files = array();
    public $server = array();
    public $env = array();

    public $ip = '127.0.0.1';
    public $time = 0; // not a sane default, we use time() if this fails
    public $microtime = 0;
    public $referrer = false;
    public $method = false;

    public function __construct() {
        if (isset($_SERVER['REQUEST_TIME_FLOAT'])) {
            // available since php 5.4
            $this->microtime = $_SERVER['REQUEST_TIME_FLOAT'];
        } else {
            // we lose accuracy, but it's a fair compromise
            $this->microtime = microtime(true);
        }

        $this->get = $_GET;
        $this->post = $_POST;
        $this->cookie = $_COOKIE;
        $this->files = $_FILES;
        $this->server = $_SERVER;
        $this->env = $_ENV;

        if (isset($_SERVER['REMOTE_ADDR'])) {
            $this->ip = $_SERVER['REMOTE_ADDR'];
        }

        $this->time = $_SERVER['REQUEST_TIME'];

        if (isset($_SERVER['HTTP_REFERER'])) {
            $this->referrer = $_SERVER['HTTP_REFERER'];
        }

        if (isset($_SERVER['REQUEST_METHOD'])) {
            $this->method = $_SERVER['REQUEST_METHOD'];
        }

        if (get_magic_quotes_gpc()) {
            $this->unescapeMagicQuotes();
        }
    }

    /**
     * @todo remove this shit when PHP 5.6 comes out and/or 5.3 is abandoned
     */
    protected function unescapeMagicQuotes() {
        $process = array(&$this->get, &$this->post, &$this->cookie);

        while (list($key, $val) = each($process)) {
            foreach ($val as $k => $v) {
                unset($process[$key][$k]);

                if (is_array($v)) {
                    $process[$key][stripslashes($k)] = $v;
                    $process[] = &$process[$key][stripslashes($k)];
                } else {
                    $process[$key][stripslashes($k)] = stripslashes($v);
                }
            }
        }
    }
}

/* vim: set ts=4 sw=4 sts=4 et: */
