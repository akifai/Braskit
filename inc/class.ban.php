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

	public $board;
	public $post;

	public static function getByID($id) {
		return banByID($id);
	}

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

class BanCreate extends Ban {
	public function __construct($ip) {
		$this->timestamp = time();

		// remove whitespace
		$ip = trim($ip);

		if ($ip === '') {
			throw new Exception('No IP entered.');
		}

		$this->ip = $ip;
	}

	/// @todo Unused
	public function setBoard(Board $board) {
		$this->board = $board;
	}

	/// @todo Unused
	public function setPost(Post $post) {
		$this->post = $post;

		if ($post->board instanceof Board) {
			// we already have a board object
			$this->setBoard($post->board);
		} elseif (strlen($post->board)) {
			try {
				// create a new board object
				$board = new Board($post->board, false, false);

				$this->setBoard($board);
			} catch (PDOException $e) {
				// database error
				throw $e;
			} catch (LogicException $e) {
				// programmatic error
				throw $e;
			}
			// ignore any other kinds of error
		}
	}

	public function setReason($reason) {
		$this->reason = trim($reason);
	}

	public function setExpire($expire) {
		if ($expire && ctype_digit($expire)) {
			// expiry time + request time = when the ban expires
			$expire += time();

			$this->expire = $expire;
		}
	}

	public function add($update = false) {
		try {
			// add the ban to the database
			$this->id = insertBan($this);
		} catch (PDOException $e) {
			// that failed for some reason - get the error code
			$errcode = $e->getCode();

			switch ($errcode) {
			case PgError::INVALID_TEXT_REPRESENTATION:
				// this happens when the IP is not valid!
				throw new Exception("Invalid IP address.");
				break;
			case PgError::UNIQUE_VIOLATION:
				// do nothing
				break;
			default:
				// unexpected error
				throw $e;
			}
		}

		if ($update) {
			$new = Ban::get($id);

			// is there a better way?
			$this->id = $new->id;
			$this->ip = $new->ip;
			$this->host = $new->host;
			$this->cidr = $new->cidr;
			$this->ipv6 = $new->ipv6;
			$this->range = $new->range;
			$this->timestamp = $new->timestamp;
			$this->expire = $new->expire;
			$this->reason = $new->reason;
		}
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
