<?php
defined('TINYIB_BOARD') or exit;

class IP {
	// CIDR ranges for IPv4
	private static $ipv4_cidr = array(
		 0 => 0x00000000,  1 => 0x80000000,  2 => 0xc0000000,
		 3 => 0xe0000000,  4 => 0xf0000000,  5 => 0xf8000000,
		 6 => 0xfc000000,  7 => 0xfe000000,  8 => 0xff000000,
		 9 => 0xff800000, 10 => 0xffc00000, 11 => 0xffe00000,
		12 => 0xfff00000, 13 => 0xfff80000, 14 => 0xfffc0000,
		15 => 0xfffe0000, 16 => 0xffff0000, 17 => 0xffff8000,
		18 => 0xffffc000, 19 => 0xffffe000, 20 => 0xfffff000,
		21 => 0xfffff800, 22 => 0xfffffc00, 23 => 0xfffffe00,
		24 => 0xffffff00, 25 => 0xffffff80, 26 => 0xffffffc0,
		27 => 0xffffffe0, 28 => 0xfffffff0, 29 => 0xfffffff8,
		30 => 0xfffffffc, 31 => 0xfffffffe, 32 => 0xffffffff,
	);

	// CIDR ranges for IPv6, in numeric binary format
	// these are generated when an IP object is created
	private static $ipv6_cidr;

	private static function generate_ipv6_cidr() {
		if (isset(self::$ipv6_cidr))
			return;

		for ($i = 0; $i <= 128; $i++) {
			$r = &self::$ipv6_cidr[$i];
			$r = str_repeat('1', $i);
			$r .= str_repeat('0', 128 - $i);
		}
	}

	// Cache variables
	private $ip, $isIPv6, $bigint, $int, $bin, $full;

	/**
	 * Constructs an object with the specified IP address.
	 *
	 * @param  mixed  The IP address, in string or integer form.
	 * @param  int    Whether or not to treat the IP address as an integer.
	 *                The parameter must be an integer with the desired IP
	 *                version, as this cannot be determined automatically.
	 * @return object IP object
	 */
	public function __construct($ip, $int = false) {
		self::generate_ipv6_cidr();

		// Should we do an integer?
		if ($int !== false && ($int == 4 || $int == 6)) {
			if (!ctype_digit($ip))
				throw new Exception('Bad integer.');

			$func = 'IntegertoIPv'.$int;
			$this->ip = $this->$func($ip);
		} else {
			$this->ip = (string)$ip;
		}

		// Verify that the IP is valid.
		if (self::validateIPv4($this->ip)) {
			// do nothing
		} elseif ($this->validateIPv6()) {
			$this->findBigintLibrary();
			$this->isIPv6 = true;
		} else {
			throw new Exception('Invalid IP address.');
		}

	}

	private function validateIPv6() {
		// No colons - it's not an IPv6 address
		if (strpos($this->ip, ':') === false)
			return false;

		// inet_pton validates the IP
		if (@inet_pton($this->ip) !== false) {
			$bin = self::IPtoBin($this->ip);
			$full = self::IPv6toFull($bin);

			// Check if this is an "embedded" IPv4 address
			if (preg_match('/^ffff:(?:0000:){5}(.*)/', $full, $m)) {
				$hex = str_replace(':', '', $m[1]);
				$octs = str_split($hex, 2);

				foreach ($octs as &$oct)
					$oct = hexdec($oct);
				unset($oct);

				// Set the IPv4 address
				$this->ip = implode('.', $octs);

				// Unset cache variables
				unset($this->full);
				unset($this->bin);

				// Lets validateIPv4() take over with the
				// embedded IPv4 address.
				return false;
			}

			return true;
		}

		// inet_pton doesn't think it's valid
		return false;
	}

	private static function validateIPv4($ip) {
		if (preg_match('/^(\d+)\.(\d+)\.(\d+)\.(\d+)$/', $ip, $m)) {
			for ($i = 1; $i <= 4; $i++)
				if (preg_match('/^0\d/', $m[$i]) || $m[$i]>255)
					return false;

			return true;
		}

		return false;
	}

	/**
	 * Get the integer form of the IP. The string type is used for
	 * compatability with 32-bit machines.
	 *
	 * @return string IP in integer form.
	 */
	public function toInteger() {
		if (isset($this->int))
			return $this->int;

		// IPv6
		if ($this->isIPv6)
			return $this->int = $this->IPv6toInteger($this->ip);

		// IPv4
		return $this->int = $this->IPv4toInteger($this->ip);
	}

	// TODO: Allow calling as static
	private function IPv6toInteger($ip) {
		$bin = self::IPtoBin($ip);
		$octs = explode(':', self::IPv6toFull($bin));
		$octs = array_reverse($octs);

		foreach ($octs as &$oct)
			$oct = hexdec($oct);
		unset($oct);

		$int = '0';
		for ($i = 0; $i < 8; $i++) {
			$int = $this->add(
				$int,
				$this->mul($octs[$i], $this->pow(65536, $i))
			);
		}
		
		return $int;
	}

	private static function IPv4toInteger($ip) {
		return sprintf('%u', ip2long($ip));
	}

	/**
	 * Returns the binary form of the IP address.
	 *
	 * @return string Binary IP address.
	 */
	public function toBin() {
		if (isset($this->bin))
			return $this->bin;

		return $this->bin = $this->IPtoBin($this->ip);
	}

	private static function IPtoBin($ip) {
		$bin = inet_pton($ip);
		return $bin;
	}

	/**
	 * Returns the full string (human-readable) representation of an IP
	 * address.
	 *
	 * @return string Human-readable IP address.
	 */
	public function toFull() {
		if (isset($this->full))
			return $this->full;

		// IPv6
		if ($this->isIPv6) {
			$bin = $this->toBin();
			return $this->full = self::IPv6toFull($bin);
		}

		// Only full IP addresses are allowed with IPv4.
		return $this->full = $this->ip;
	}

	private static function IPv6toFull($bin) {
		$hex = bin2hex($bin);
		return implode(':', str_split($hex, 4));
	}

	public function toShort() {
		static $short;
		if (isset($this->short))
			return $short;

		$this->short = $this->BinToShort($this->toBin());

		return $this->short;
	}

	private static function BinToShort($bin) {
		return inet_ntop($bin);
	}

	/**
	 * Converts an integer to an IPv4 address
	 *
	 * @param  string Integer
	 * @return string IPv4 address
	 */
	public function IntegerToIPv4($int) {
		return long2ip($int);
	}

	/**
	 * Converts an integer to an IPv6 address
	 *
	 * @param  string Integer
	 * @return string IPv4 address
	 */
	public function IntegerToIPv6($int) {
		// Stolen from http://stackoverflow.com/questions/1120371/
		// I have no idea how this works, lol.
		static $exp = array(
			'79228162514264337593543950336',
			'18446744073709551616',
			'4294967296',
			'0',
		);

		$p = array();
		for ($i = 0; $i < 3; $i++) {
			$p[$i] = $this->div($int, $exp[$i]);
			$int = $this->sub($int, $this->mul($p[$i], $exp[$i]));
		}
		$p[3] = $int;

		// This fixes signed integers or whatever, idk
		foreach ($p as &$part) {
			if ($this->cmp($part, '2147483647') == 1)
				$part = $this->sub($part, '4294967296');
			$part = (int)$part;
		}
		unset($part);

		$ip = inet_ntop(pack('N4', $p[0], $p[1], $p[2], $p[3]));

		if (preg_match('/^(\d+)\.(\d+)\.(\d+)\.(\d+)$/', $ip, $m)) {
			// inet_ntop likes to return IPv4 addresses sometimes
			return sprintf('::%x%x:%x%x',$m[0],$m[1],$m[2],$m[3]);
		} elseif (preg_match('/^ffff::\d+.\d+.\d+.\d+$/', $ip)) {
			// IPv6-mapped IPv4 addresses
			return substr($ip, 6);
		}

		return $ip;
	}

	/**
	 * Find out if an IP address is in an IP range, using CIDR notation.
	 * Note that the range is assumed to be valid and of the same IP version
	 * as the IP itself.
	 *
	 * @param  string  An IP range (e.g. 192.168.0.0).
	 * @param  integer Range prefix size, in CIDR notation.
	 * @return bool    IP address is in specified range.
	 */
	public function inRange($range, $cidr) {
		// IPv6
		if ($this->isIPv6) {
			$ipbin = $this->toBin();
			$rangebin = self::IPtoBin($range);
			return self::IPv6inRange($ipbin, $rangebin, $cidr);
		}

		// IPv4
		$range = self::IPv4toInteger($range);
		return self::IPv4inRange($this->toInteger(), $range, $cidr);
	}

	private static function IPv6inRange($ipbin, $rangebin, $cidr) {
		$cidr = self::$ipv6_cidr[$cidr];
		$ipand = self::str_and($ipbin, $cidr);
		$rangeand = self::str_and($rangebin, $cidr);

		return $ipand === $rangeand;
	}

	private static function IPv4inRange($ipint, $range, $cidr) {
		$mask = self::$ipv4_cidr[$cidr];
		return ($ipint & $mask) == ($range & $mask);
	}


	/* =========================
	 * Math utils
	 * ========================= */

	 private function findBigintLibrary() {
		// Find a method of doing stuff with big numbers
		if (extension_loaded('bcmath')) {
			$this->bigint = 'bcmath';
			bcscale(0);
		} elseif (extension_loaded('gmp')) {
			$this->bigint = 'gmp';
		} else {
			throw new Exception('No IPv6 support.');
		}
	 }

	private function str_and($x, $y) {
		// This function is only needed for IPv6, so all strings are
		// assumed to be 128 bits in length.
		static $cache = array();

		// Return cached results (if any)
		$cache_key = "$x:$y";

		if (isset($cache[$cache_key]))
			return $cache[$cache_key];

		// Convert $x to its numeric binary representation
		$x_bin = '';
		foreach (str_split($x) as $chr) {
			$x_bin .= sprintf('%08b', ord($chr));
		}

		// Compare all bits
		$and = '';

		for ($i = 0; $i < 128; $i++) {
			if ($x_bin[$i] === '1' && $y[$i] === '1')
				$and .= '1';
			else 
				$and .= '0';
		}

		return $cache[$cache_key] = $and;
	}

	private function add($x, $y) {
		switch ($this->bigint) {
		case 'gmp':
			return gmp_strval(gmp_add($x, $y));
		case 'bcmath':
			return bcadd($x, $y);
		}
	}

	private function cmp($x, $y) {
		switch ($this->bigint) {
		case 'gmp':
			return gmp_strval(gmp_cmp($x, $y));
		case 'bcmath':
			return bccomp($x, $y);
		}
	}

	private function div($x, $y) {
		switch ($this->bigint) {
		case 'gmp':
			return gmp_strval(gmp_div($x, $y));
		case 'bcmath':
			return bcdiv($x, $y);
		}
	}

	private function mul($x, $y) {
		switch ($this->bigint) {
		case 'gmp':
			return gmp_strval(gmp_mul($x, $y));
		case 'bcmath':
			return bcmul($x, $y);
		}
	}

	private function pow($x, $y) {
		switch ($this->bigint) {
		case 'gmp':
			return gmp_strval(gmp_pow($x, $y));
		case 'bcmath':
			return bcpow($x, $y);
		}
	}

	private function sub($x, $y) {
		switch ($this->bigint) {
		case 'gmp':
			return gmp_strval(gmp_sub($x, $y));
		case 'bcmath':
			return bcsub($x, $y);
		}
	}
}
