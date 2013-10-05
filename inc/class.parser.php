<?php
defined('TINYIB') or exit;

abstract class Parser {
	protected $raw = '';
	protected $parsed = '';
	protected $stripped = '';

	abstract protected function parse();

	public function __construct($text) {
		$this->raw = $text;

		$this->normaliseInput();

		if (!strlen(trim($this->raw)))
			return;

		$this->parse();
	}

	/**
	 * @return string The raw, normalised input.
	 */
	public function getRaw() {
		return $this->raw;
	}

	/**
	 * @return string The parsed input in HTML format.
	 */
	public function getParsed() {
		return $this->parsed;
	}

	/**
	 * @return string The text itself with no formatting or linebreaks.
	 */
	public function getStripped() {
		return $this->stripped;
	}

	/**
	 * Removes characters which are undesirable in all circumstances, such as
	 * control codes and invalid Unicode sequences.
	 */
	protected function normaliseInput() {
		// remove illegal characters
		$this->raw = preg_replace(
			'/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/',
			'',
			$this->raw
		);

		// remove invalid unicode
		if (version_compare(PHP_VERSION, '5.4.0', '>=')) {
			$this->raw = htmlspecialchars_decode(
				htmlspecialchars($this->raw, ENT_SUBSTITUTE, 'UTF-8')
			);
		}

		// normalise unicode
		if (extension_loaded('intl') && !Normalizer::isNormalized($this->raw)) {
			$this->raw = Normalizer::normalize($this->raw);
		}
	}

	/**
	 * Escapes HTML characters in a string.
	 *
	 * @param $str string
	 * @return string
	 */
	protected static function escape($str) {
		$flags = ENT_QUOTES;

		if (version_compare(PHP_VERSION, '5.4.0', '>=')) {
			$flags |= ENT_HTML5;
		}

		return htmlspecialchars($str, $flags, 'UTF-8');
	}

}

/**
 * A parser for Wakabamark.
 *
 * The "spec" can be found here: http://wakaba.c3.cx/docs/docs.html#WakabaMark
 *
 * @todo Inline formatting isn't done yet. Block-level formatting is more or
 *       less complete.
 */
class Parser_Wakabamark extends Parser {
	const RE_PARAGRAPH = '/^\s*$/';
	const RE_LIST_UL = '/^([*+-])\s+/';
	const RE_LIST_OL = '/^(\d+)\.\s+/';
	const RE_PRETEXT = '/^(?: {4}|\t)(.*)/';
	const RE_QUOTE = '/^>(?!\>\d+)/';

	protected $lines = array();
	protected $inParagraph = false;

	protected function parse() {
		// Split lines
		$this->lines = preg_split('/\r?\n|\r/', $this->raw);

		while (($line = array_shift($this->lines)) !== null) {
			// End of paragraph
			if (preg_match(self::RE_PARAGRAPH, $line)) {
				$this->endParagraph();
				continue;
			}

			// Unordered lists
			if (preg_match(self::RE_LIST_UL, $line, $matches)) {
				$this->endParagraph();
				$this->doUnorderedList($line, $matches[1]);
				continue;
			}

			// Ordered lists
			if (preg_match(self::RE_LIST_OL, $line, $matches)) {
				$this->endParagraph();
				$this->doOrderedList($line, $matches);
				continue;
			}

			// Blockquote
			if (preg_match(self::RE_QUOTE, $line)) {
				$this->endParagraph();
				$this->doQuote($line);
				continue;
			}

			// Preformatted text
			if (preg_match(self::RE_PRETEXT, $line, $matches)) {
				$this->endParagraph();
				$this->doPreText($line, $matches);
				continue;
			}

			// Normal text
			$this->doParagraph($line);
		}

		$this->endParagraph();

		$this->stripped = trim($this->stripped);
	}

	/**
	 * Do parsing of inline stuff like bold and italic text.
	 */
	protected function parseInline($text) {
		// TODO
		$this->stripped .= "$text ";
		$this->parsed .= $this->escape($text);
	}

	protected function nextLine() {
		return isset($this->lines[0]) ? $this->lines[0] : false;
	}

	/**
	 * Closes the current paragraph, if any.
	 */
	protected function endParagraph() {
		if ($this->inParagraph) {
			$this->parsed .= '</p>';
			$this->inParagraph = false;
		}
	}

	/**
	 * Parses unordered lists.
	 */
	protected function doUnorderedList($line, $token) {
		$this->parsed .= '<ul>';

		// In wakaba, a new list is started if we change the bullet.
		// We emulate this behaviour.
		$regex = '/^'.preg_quote($token).'\s/';

		do {
			$this->parsed .= '<li>';

			// Remove bullet and trailing whitespace
			$line = trim(substr($line, strlen($token)));

			$this->parseInline($line);

			// Line breaks are allowed in list items if the next line has one or
			// more spaces at the beginning. Find those lines, parse them and
			// add them.
			while (
				preg_match('/^\s+[^\s]/', $this->nextLine()) &&
				$line = array_shift($this->lines)
			) {
				$this->parsed .= '<br>';
				$this->parseInline(trim($line));
			}

			// End of list item
			$this->parsed .= '</li>';
		} while (
			// Find new list items
			preg_match($regex, $this->nextLine()) &&
			$line = array_shift($this->lines)
		);

		// End of list
		$this->parsed .= '</ul>';
	}

	/**
	 * Parses ordered lists.
	 */
	protected function doOrderedList($line, $matches) {
		$this->parsed .= '<ol>';

		do {
			$num = (int)$matches[1];

			// Strip line number away
			$line = preg_replace(self::RE_LIST_OL, '', $line);

			if (isset($next) && $num !== $next || !isset($next) && $num !== 1) {
				// Irregular list ordering
				$this->parsed .= '<li value="'.$num.'">';
			} else {
				$this->parsed .= '<li>';
			}

			$this->parseInline($line);

			// Line breaks
			while (
				preg_match('/^\s+[^\s]/', $this->nextLine()) &&
				$line = array_shift($this->lines)
			) {
				$this->parsed .= '<br>';
				$this->parseInline(trim($line));
			}

			$this->parsed .= '</li>';

			// expected number for the next list item
			$next = $num + 1;
		} while (
			// Find new list items
			preg_match(self::RE_LIST_OL, $this->nextLine(), $matches) &&
			$line = array_shift($this->lines)
		);

		$this->parsed .= '</ol>';
	}

	/**
	 * Parses sections with preformatted text.
	 */
	protected function doPreText($line, $matches) {
		if (!strlen(trim($line))) {
			// ignore blank lines starting the code block
			return;
		}

		$this->parsed .= '<pre><code>';
		$this->parsed .= $this->escape($matches[1]);

		$breaks = 1;

		while (
			preg_match(self::RE_PRETEXT, $this->nextLine(), $matches) &&
			$line = array_shift($this->lines)
		) {
			if (!strlen(trim($line))) {
				// never have trailing spaces
				++$breaks;
			} else {
				$this->parsed .= str_repeat('<br>', $breaks);
				$this->parsed .= $this->escape($matches[1]);

				$breaks = 1;
			}
		}

		$this->parsed .= '</code></pre>';
	}

	/**
	 * Parses quote sections.
	 */
	protected function doQuote($line) {
		$this->parsed .= '<blockquote>';
		$this->parseInline($line);

		while (
			preg_match(self::RE_QUOTE, $this->nextLine()) &&
			$line = array_shift($this->lines)
		) {
			$this->parsed .= '<br>';
			$this->parseInline($line);
		}

		$this->parsed .= '</blockquote>';
	}

	/**
	 * Parses regular lines.
	 *
	 * @param $line string The line to be parsed.
	 */
	protected function doParagraph($line) {
		if (!$this->inParagraph) {
			$this->parsed .= '<p>';
			$this->inParagraph = true;
		} else {
			$this->parsed .= '<br>';
		}

		$this->parseInline($line);
	}
}
