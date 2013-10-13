<?php
defined('TINYIB') or exit;

class Ban {
	public $id = null;
	public $ip = null;
	public $host = null;
	public $cidr = null;
	public $ipv6 = false;
	public $range = false;
	public $timestamp = '';
	public $expire = null;
	public $reason = '';

	/**
	 * Check if an IP is banned.
	 *
	 * @throws BanException if the IP is banned
	 */
	public static function check($ip, $time = false) {
		if ($time === false)
			$time = time();

		$bans = activeBansByIP($ip, $time);

		if (!$bans) {
			// not banned
			return;
		}

		$e = new BanException("Host is banned ($ip)");
		$e->setBans($bans);

		$e->ip = $ip;

		throw $e;
	}
}

class BanException extends Exception implements Iterator {
	public $ip = null;
	protected $bans = array();
	protected $pos = 0;

	public function setBans(Array $bans = array()) {
		for ($i = count($bans); $i--;) {
			if (!($bans[$i] instanceof Ban)) {
				throw new LogicException('Array item must be Ban object.');
			}
		}

		$this->bans = $bans;
	}

	public function current() {
		return $this->bans[$this->pos];
	}

	public function key() {
		return $this->pos;
	}

	public function next() {
		++$this->pos;
	}

	public function rewind() {
		$this->pos = 0;
	}

	public function valid() {
		return isset($this->bans[$this->pos]);
	}
}
