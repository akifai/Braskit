<?php
defined('TINYIB') or exit;

class HTMLException extends Exception {
	const HTML_MESSAGE = 9001;

	public function __construct($message) {
		parent::__construct($message, self::HTML_MESSAGE);
	}
}

class UserException extends Exception {}
