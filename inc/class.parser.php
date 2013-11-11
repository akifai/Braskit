<?php

/**
 * Methods and properties which are common for all parsers.
 */
abstract class Parser {
	/**
	 * The raw, normalised input.
	 * @var string
	 */
	public $raw = '';

	/**
	 * The parsed input in HTML format.
	 * @var string
	 */
	public $parsed = '';

	/**
	 * The text itself with no formatting or line breaks.
	 * @var string
	 */
	public $stripped = '';

	/**
	 * Removes characters which are undesirable in all circumstances, such as
	 * control codes and invalid Unicode sequences.
	 */
	public static function normaliseInput($text) {
		// remove illegal characters
		$text = preg_replace(
			'/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/',
			'',
			$text
		);

		// remove invalid unicode
		if (version_compare(PHP_VERSION, '5.4.0', '>=')) {
			$text = htmlspecialchars_decode(
				htmlspecialchars($text, ENT_SUBSTITUTE, 'UTF-8')
			);
		}

		// normalise unicode
		if (extension_loaded('intl') && !Normalizer::isNormalized($text)) {
			$text = Normalizer::normalize($text);
		}

		return $text;
	}

	/**
	 * Escapes HTML characters in a string.
	 *
	 * @param $str string
	 * @return string
	 */
	public static function escape($str) {
		$flags = ENT_QUOTES;

		if (version_compare(PHP_VERSION, '5.4.0', '>=')) {
			$flags |= ENT_HTML5;
		}

		return htmlspecialchars($str, $flags, 'UTF-8');
	}
}

/**
 * Abstract class which provides the facilities needed for parsing blocks of
 * text.
 */
abstract class BlockParser extends Parser {
	protected $lines = array();

	/**
	 * A list of callables which perform substitutions on the text nodes
	 * prior to appending them to the parsed output. Typically used for
	 * linkifying citations and stuff like that.
	 * @var array
	 */
	protected $modifiers = array();

	/**
	 * A definition of the block syntax. See Parser_Wakabamark for an example.
	 * @var array
	 */
	protected $syntax = array();

	/**
	 * The method to execute when no "special" syntax matched the line.
	 * @var string
	 */
	protected $fallbackMethod;

	/**
	 * The method to call when the parsing is done. Typically used for closing
	 * any elements which are left open.
	 * @var string
	 */
	protected $endMethod;

	public function __construct($text, Array $textModifiers = array()) {
		$this->modifiers = $textModifiers;

		$this->raw = $this->normaliseInput($text);

		if (!strlen(trim($this->raw)))
			return;

		$this->parse();
	}

	protected function parse() {
		// Split lines
		$this->lines = preg_split('/\r?\n|\r/', $this->raw);

		// Loop through all the lines
		while (($line = array_shift($this->lines)) !== null) {
			// Loop through all the different syntax types
			foreach ($this->syntax as $node) {
				if (!$node['regex']) {
					// end the fallback state
					$this->{$this->endMethod}();

					// no regex, do the callback
					$this->{$node['method']}($line);

					// skip to next line
					continue 2;
				} elseif (preg_match($node['regex'], $line, $matches)) {
					// end the fallback state
					$this->{$this->endMethod}();

					// there was a regex and it matched the current line
					if ($node['method']) {
						$this->{$node['method']}($line, $matches);
					}

					// skip to next line
					continue 2;
				}
			}

			// no syntax matched, do the fallback
			$this->{$this->fallbackMethod}($line);
		}

		$this->{$this->endMethod}();

		$this->stripped = trim($this->stripped);
	}
}

/**
 * A parser for Wakabamark (http://wakaba.c3.cx/docs/docs.html#WakabaMark).
 *
 * Although we do comply with the spec, this differs from the reference
 * implementation (Wakaba/Kareha) in various ways:
 *
 *   - We don't have ^H for strikethrough. This is an easter egg in
 *     Wakaba/Kareha which causes so many problems it's not even worth adding.
 *   - Inline syntax (bold/italic/code) can actually be nested!
 *
 * There are probably a billion other little difference I'm unaware of or which
 * I forgot.
 */
class Parser_Wakabamark extends BlockParser {
	const RE_PARAGRAPH = '/^\s*$/';
	const RE_LIST_UL = '/^([*+-])\s+/';
	const RE_LIST_OL = '/^(\d+)\.\s+/';
	const RE_PRETEXT = '/^(?: {4}|\t)(.*)/';
	const RE_QUOTE = '/^>(?!\>(?:\>\/[^\s\/]+\/)?\d+)/';

	protected $inParagraph = false;

	protected $syntax = array(
		// End of paragraph
		array(
			'regex' => self::RE_PARAGRAPH,
			'method' => false,
		),

		// Unordered lists
		array(
			'regex' => self::RE_LIST_UL,
			'method' => 'doUnorderedList',
		),

		// Ordered lists
		array(
			'regex' => self::RE_LIST_OL,
			'method' => 'doOrderedList'
		),

		// Blockquote
		array(
			'regex' => self::RE_QUOTE,
			'method' => 'doQuote',
		),

		// Preformatted text
		array(
			'regex' => self::RE_PRETEXT,
			'method' => 'doPreText',
		),
	);

	// Normal text
	protected $fallbackMethod = 'doParagraph';

	// Close the open paragraph, if any.
	protected $endMethod = 'endParagraph';

	/**
	 * Do parsing of inline stuff like bold and italic text.
	 */
	protected function parseInline($text) {
		// yo dawg, parser in your parser, etc
		$parser = new InlineParser_Wakabamark($text, $this->modifiers);

		$this->parsed .= $parser->parsed;
		$this->stripped .= $parser->stripped;
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
	protected function doUnorderedList($line, $matches) {
		$this->parsed .= '<ul>';

		$token = $matches[1];

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

class InlineParseTree {
	public $nodes = array();
	public $markup;
	public $parent;

	public function __construct($parent = false, $markup = false) {
		$this->markup = $markup;
		$this->parent = $parent;
	}

	public function add($node) {
		array_push($this->nodes, $node);
	}

	public function copyTo(InlineParseTree $tree) {
		foreach ($this->nodes as $node) {
			$tree->add($node);
		}
	}

	public function pop() {
		return array_pop($this->nodes);
	}
}

/**
 * @todo Document this.
 */
abstract class InlineParser extends Parser {
	protected $stack = array();
	protected $tree;

	protected $markup = array();
	protected $modifiers = array();
	
	public function __construct($text, Array $modifiers = array()) {
		$this->modifiers = $modifiers;

		$this->raw = $text;

		$this->parse();
	}

	protected function parse() {
		foreach ($this->markup as &$markup) {
			if (!isset($markup['close_token'])) {
				$markup['close_token'] = $markup['token'];
			}
		}

		$this->makeStack();
		$this->makeTree();

		$this->parsed = $this->formatTree($this->tree);
	}

	protected function sortByLength($a, $b){
	    return strlen($b) - strlen($a);
	}

	protected function makeStack() {
		$this->stack = array();

		$regex = array();

		// TODO: We only need to run the code from here and until the preg_split
		// once per session, but it gets done every time we invoke an inline
		// parser. Waste of resources.
		foreach ($this->markup as $markup) {
			for ($i = 0; $i < count($markup['token']); $i++) {
				$regex[] = str_replace('\+', '+', preg_quote($markup['token'][$i], '/'));

				if ($markup['close_token'][$i] !== $markup['token'][$i]) {
					$regex[] = str_replace('\+', '+', preg_quote($markup['close_token'][$i], '/'));
				}
			}
		}

		usort($regex, array($this, 'sortByLength'));

		$regex = '/('.implode('|', $regex).')/';

		$stack = preg_split($regex, $this->raw, -1, PREG_SPLIT_DELIM_CAPTURE);

		$new_stack = array();
		$temp_stack = false;

		foreach ($stack as $part) {
			if ($temp_stack !== false) {
				$temp_stack .= $part;

				if (strpos($part, ' ') !== false) {
					// Space found. Stop treating everything literally.
					$new_stack[] = $temp_stack;
					$temp_stack = false;
				}
			} elseif (preg_match('@https?://@', $part)) {
				// URL found. Treat everything literally until we find whitespace.
				$temp_stack = '';
				$temp_stack .= $part;
			} else {
				$new_stack[] = $part;
			}
		}

		if ($temp_stack !== false) {
			$new_stack[] = $temp_stack;
		}

		$this->stack = array_filter($new_stack, 'strlen');
	}

	protected function isToken($tokens, $part) {
		foreach ($tokens as $i => $token) {
			if (
				strlen($token) > 1 && substr($token, -1) === '+' &&
				str_repeat(substr($token, 0, -1), strlen($part)) === $part ||
				$token === $part
			) {
				return $i;
			}
		}

		return false;
	}

	protected function isOpen($nest, $token, $check_other_tokens = false) {
		foreach ($nest as $markup) {
			if (!$check_other_tokens && $markup['close_token'] === $token) {
				return true;
			} elseif ($this->isToken($markup['token'], $token) !== false) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @todo Maybe add some sort of sane debugging stuff for this?
	 * @todo '*_foo_*' results in a fatal error.
	 * @todo '*foo_' kills the underscore.
	 */
	protected function makeTree() {
		$this->tree = new InlineParseTree();

		$current = $this->tree;
		$nest = array();

		foreach ($this->stack as $part) {
			$this_markup = false;

			foreach ($this->markup as $markup) {
				$token_num = $this->isToken($markup['token'], $part);

				if ($token_num !== false) {
					$this_markup = $markup;
					$this_markup['open_token'] = $markup['token'][$token_num];
					$this_markup['real_open'] = $part;
					$this_markup['close_token'] = $markup['close_token'][$token_num];
					$this_markup['state'] = 'open';
				}

				$token_num = $this->isToken($markup['close_token'], $part);

				if ($token_num !== false) {
					$unknown_state = isset($this_markup['state']);

					$this_markup = $markup;
					$this_markup['open_token'] = $markup['token'][$token_num];
					$this_markup['close_token'] = $markup['close_token'][$token_num];

					if ($unknown_state) {
						if ($this->isOpen($nest, $part)) {
							$this_markup['state'] = 'close';
						} else {
							$this_markup['real_open'] = $part;
							$this_markup['state'] = 'open';
						}
					} else {
						$this_markup['state'] = 'close';
					}
				}

				if ($this_markup !== false) {
					break;
				}
			}

			if ($this_markup !== false) {
				$nest_current = end($nest);

				if ($this_markup['state'] == 'close') {
					if ($nest_current && $nest_current['close_token'] === $part) {
						// Correct nesting.
						// Move back up a layer.
						array_pop($nest);

						$current = $current->parent;
					} elseif ($this->isOpen($nest, $part)) {
						// Invalid. You're closing a tag out of order.
						for ($x = count($nest); $x--;) {
							$markup = array_pop($nest);

							if ($markup['close_token'] === $part) {
								break;
							}

							$defunct = $current;

							// Go back up a layer.
							$current = $defunct->parent;

							// Remove the invalid layer from the tree.
							$current->pop();

							// Move the contents of the invalid layer into its parent.
							$current->add($markup['real_open']);
							$defunct->copyTo($current);
						}

						// Go back up a step again.
						$current = $current->parent;
					} else {
						// Close tag out of nowhere.
						$current->add($part);
					}
				} elseif ($this_markup['state'] == 'open') {
					if ($this_markup['open_token'] === $this_markup['close_token']) {
						$this_markup['close_token'] = $part;
					}

					if (
						$nest_current &&
						!$nest_current['children'] &&
						$nest_current['close_token'] !== $part
					) {
						$current->add($part);
					} elseif ($this->isOpen($nest, $part, true)) {
						// Already open, but with another token, e.g. "**"
						// instead of "__". Treat literally.
						$current->add($part);
					} else {
						// Starting a new layer.
						$newNode = new InlineParseTree($current, $this_markup);
						$current->add($newNode);

						$current = $newNode;
						$nest[] = $this_markup;
					}
				}
			} else {
				// Add plain text.
				$current->add($part);
			}
		}

		// If $nest is not empty at this point, then something was not closed.
		for ($i = count($nest); $i--;) {
			$defunct = $current;
			$current = $defunct->parent;

			$current->pop();
			$current->add($nest[$i]['real_open']);
			$defunct->copyTo($current);
		}
	}
	
	protected function doLinks($text, $callback_for_the_bits_outside) {
		$offset = 0;
		$output = '';
		
		// Find all links
		preg_match_all(
			'@((?:https?://|ftp://|irc://)[^\s<>()"]+?(?:\([^\s<>()"]*?\)[^\s<>()"]*?)*)((?:\s|<|>|"|\.||\]|!|\?|,)*(?:[\s<>()"]|$))@u',
			$text,
			$matches,
			PREG_OFFSET_CAPTURE | PREG_SET_ORDER
		);

		foreach ($matches as $match) {
			// Preceding text
			$before = substr($text, $offset, $match[0][1] - $offset);
		
			// Following text
			$after = $match[2][0];
		
			// Apply markup to the parts outside the URL
			if ($callback_for_the_bits_outside !== false) {
				$before = call_user_func($callback_for_the_bits_outside, $before);
				$after = call_user_func($callback_for_the_bits_outside, $after);
			}
		
			// Build and append link
			$output .= sprintf('%s<a href="%s" rel="nofollow">%s</a>%s',
				$before,
				$this->escape($match[1][0]),
				$this->escape($match[1][0]),
				$after
			);
		
			// Increment offset
			$offset = $match[0][1] + strlen($match[0][0]);
		}
		
		unset($before, $after, $match);
		
		// Do the very last bit
		$end = substr($text, $offset);

		if ($callback_for_the_bits_outside !== false) {
			$end = call_user_func($callback_for_the_bits_outside, $end);
		}

		$output .= $end;
		
		return $output;
	}

	protected function doText($text) {
		$text = $this->escape($text);
		
		foreach ($this->modifiers as $modifier) {
			// the modifier takes escaped text as a reference
			call_user_func_array($modifier, array(&$text));
		}

		return $text;
	}

	protected function formatText($text) {
		return $this->doLinks($text, array($this, 'doText'));
	}

	protected function formatTree($tree) {
		$text = '';

		foreach ($tree->nodes as $node) {
			if ($node instanceof InlineParseTree) {
				$text .= $this->formatTree($node);
			} else {
				// text node
				$this->stripped .= $node;
				$text .= $this->formatText($node);
			}
		}

		if ($text === '') {
			// Empty. Example: "****". Treat literally?
			return $tree->markup['open_token'].$tree->markup['close_token'];
		}

		if ($tree->markup) {
			if (isset($tree->markup['callback'])) {
				$text = call_user_func($tree->markup['callback'], $text);
			}

			return $tree->markup['format'][0].$text.$tree->markup['format'][1];
		}

		return $text;
	}
}

class InlineParser_Wakabamark extends InlineParser {
	protected $markup = array(
		array(
			'token' => array('*', '_'),
			'format' => array('<em>', '</em>'),
			'children' => true,
		),
		array(
			'token' => array('**', '__'),
			'format' => array('<strong>', '</strong>'),
			'children' => true,
		),
		array(
			'token' => array('`+'),
			'format' => array('<code>', '</code>'),
			'callback' => 'trim',
			'children' => false,
		),
	);
}
