<?php
defined('TINYIB') or exit;

/**
 * @todo Writing spam definitions and other stuff.
 * @todo rule diffing
 */
class Spam {
	public $enable_autobans = true;
	
	public $word = ''; // the matching word
	public $no_ban = true; // whether or not to ban the poster

	protected $cache_key = 'spam_defs';
	protected $regex = false;
	protected $files = array();

	// used when compiling
	protected $word_parts = array();
	protected $re_parts = array();


	//
	// External API
	//

	public function __construct($defs) {
		if ($this->loadFromCache())
			return;

		$this->loadDefinitions($defs);

		$this->compileRegex();

		$this->saveToCache();
	}

	public function arrayMatches(array $strings = array()) {
		foreach ($strings as $string)
			if ($this->stringMatches($string))
				return true;

		return false;
	}

	public function stringMatches($string) {
		if ($this->regex === null)
			return false;

		$matched = (bool)preg_match($this->regex, $string, $matches);

		if ($matched) {
			$this->word = $matches[1];
			$this->no_ban = isset($matches['no_ban']); // regex: (?<no_ban>)
		}

		return $matched;
	}


	//
	// Storage and cache
	//

	protected function loadFromCache() {
		$cache = get_cache($this->cache_key);

		if ($cache === false)
			return;

		$this->regex = $cache;
	}

	protected function clearCache() {
		delete_cache($this->cache_key);
	}

	protected function saveToCache() {
		set_cache($this->cache_key, $this->regex);
	}

	/**
	 * Retrieve the spam definitions
	 */
	protected function loadDefinitions($defs) {
		$row = getLatestSpamRules();

		if ($row === false)
			return;

		$this->parse($row['rules']);
	}


	//
	// Parser and compilation methods
	//

	const RE_WITH_COMMENT =
		'!^/([^\\\\\\/]*(?:\\\\.[^\\\\\\/]*)*)/([xism]{0,4})(?:\s+#.*$)?!';
	const RE_WITHOUT_COMMENT = '!^/(.*)/([xism]{0,4})$!';

	protected function parse($string) {
		$lines = preg_split('/\r?\n|\r/', $string, -1, PREG_SPLIT_NO_EMPTY);

		foreach ($lines as $line) {
			// remove whitespace
			$line = trim($line);

			// skip blank lines
			if (!strlen($line) || substr($line, 0, 1) === '#')
				continue;

			// check if this is a regex
			// if there isn't a comment in the line, we can allow slashes
			// between the literals without escaping
			$regex = (strpos($line, '#') !== false)
				? self::RE_WITH_COMMENT
				: self::RE_WITHOUT_COMMENT;

			if (preg_match($regex, $line, $matches)) {
				$str = '';

				// escape slashes
				$matches[1] = preg_replace('@(?!\\\\)/@', '\\/', $matches[1]);

				// last backslash
				$matches[1] = preg_replace(
					'!(^|[^\\\\])\\\\$!',
					'\1\\\\\\\\',
					$matches[1]
				);

				// flags
				if ($matches[2]) {
					// the outmost (?:...) is there to keep the flags scoped
					$str .= '(?:(?'.$matches[2].')';

					if (strpos($matches[2], 'x') !== false) {
						// prevent issues with the hash sign being interpreted
						// as a comment marker when the 'x' modifier is used
						$matches[1] .= "\n";
					}
				}

				$str .= $matches[1];

				if ($matches[2])
					$str .= ')';

				// we need to validate the regex by itself
				if (@preg_match("/$str/", '') !== false)
					$this->re_parts[] = $str;

				continue;
			}

			// get rid of comments in word matches
			if (preg_match('/(^.+?)\s+#/', $line, $matches))
				$line = $matches[1];

			// case-insensitive by default
			$this->word_parts[] = preg_quote($line, '/');
		}
	}

	/**
	 * Creates a regular expression for spam matching.
	 * Should be called after files have been parsed, otherwise it'll set
	 * $this->regex to null.
	 */
	protected function compileRegex() {
		$this->re_parts = array_unique($this->re_parts);
		$this->word_parts = array_unique($this->word_parts);

		if (!$this->re_parts && !$this->word_parts) {
			$this->regex = null;
			return;
		}

		$this->regex = '/(';
		$this->regex .= implode('|', $this->re_parts);

		if ($this->word_parts) {
			if ($this->re_parts)
				$this->regex .= '|';

			$this->regex .= '(?i)';
			$this->regex .= implode('|', $this->word_parts);
		}

		$this->regex .= ')/Su';
	}
}
