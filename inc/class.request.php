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

		if (isset($_SERVER['REQUEST_TIME_FLOAT'])) {
			// available since php 5.4
			$this->microtime = $_SERVER['REQUEST_TIME_FLOAT'];
		} else {
			// we lose accuracy, but it's a fair compromise
			$this->microtime = microtime(true);
		}

		if (isset($_SERVER['REQUEST_METHOD'])) {
			$this->method = $_SERVER['REQUEST_METHOD'];
		}
	}
}
