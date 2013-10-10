<?php
defined('TINYIB') or exit;

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
	public function copy_to(InlineParseTree $tree) {
		foreach ($this->nodes as $node)
			$tree->add($node);
	}
	public function pop() {
		return array_pop($this->nodes);
	}
}

abstract class InlineParser {
	protected $raw = '';
	protected $parsed = '';
	protected $stack = array();
	protected $tree;

	protected $markup = array();

	abstract protected function defineMarkup();
	
	public function __construct($text) {
		$this->defineMarkup();

		$this->raw = $text;
		$this->parse();
	}

	public function getParsed() {
		return $this->parsed;	
	}

	protected function parse() {
		foreach ($this->markup as &$markup) {
			if (!isset($markup['close_token']))
				$markup['close_token'] = $markup['token'];
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
		foreach ($this->markup as $markup) {
			for ($i = 0; $i < count($markup['token']); $i++) {
				$regex[] = str_replace('\+', '+', preg_quote($markup['token'][$i], '/'));
				if ($markup['close_token'][$i] !== $markup['token'][$i])
					$regex[] = str_replace('\+', '+', preg_quote($markup['close_token'][$i], '/'));
			}
		}
		usort($regex, array($this, 'sortByLength'));
		$regex = '/(' . implode('|', $regex) . ')/';
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
		if ($temp_stack !== false)
			$new_stack[] = $temp_stack;
		$this->stack = array_filter($new_stack, 'strlen');
	}

	protected function isToken($tokens, $part) {
		foreach ($tokens as $i => $token) {
			if (strlen($token) > 1 && substr($token, -1) == '+') {
				if (str_repeat(substr($token, 0, -1), strlen($part)) === $part)
					return $i;
			} else {
				if ($token === $part)
					return $i;
			}
		}
		return false;
	}

	protected function isOpen($nest, $token, $check_other_tokens = false) {
		foreach ($nest as $markup) {
			if (!$check_other_tokens) {
				if ($markup['close_token'] === $token)
					return true;
			} else {
				if ($this->isToken($markup['token'], $token) !== false)
					return true;
			}
		}
		return false;
	}

	protected function makeTree() {
		$this->tree = new InlineParseTree();
		$current = $this->tree;
		$nest = array();
		foreach ($this->stack as $part) {
			$this_markup = false;
			foreach ($this->markup as $markup) {
				if (($token_num = $this->isToken($markup['token'], $part)) !== false) {
					$this_markup = $markup;
					$this_markup['open_token'] = $markup['token'][$token_num];
					$this_markup['real_open'] = $part;
					$this_markup['close_token'] = $markup['close_token'][$token_num];
					$this_markup['state'] = 'open';
				}
				if (($token_num = $this->isToken($markup['close_token'], $part)) !== false) {
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
				if ($this_markup !== false)
					break;
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
						for ($x = count($nest) - 1; $x >= 0; $x--) {
							$markup = array_pop($nest);
							if ($markup['close_token'] === $part)
								break;
							$defunct = $current;
							// Go back up a layer.
							$current = $defunct->parent;
							// Remove the invalid layer from the tree.
							$current->pop();
							// Move the contents of the invalid layer into its parent.
							$current->add($markup['real_open']);
							$defunct->copy_to($current);
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
					if ($nest_current && !$nest_current['children'] && $nest_current['close_token'] !== $part) {
						$current->add($part);
					} elseif ($this->isOpen($nest, $part, true)) {
						// Already open, but with another token. Eg. "**" instead of "__'.
						// Treat literally.
						$current->add($part);
					} else {
						// Starting a new layer.
						$current->add($newNode = new InlineParseTree($current, $this_markup));
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
		for ($i = count($nest) - 1; $i >= 0; $i--) {
			$defunct = $current;
			$current = $defunct->parent;
			$current->pop();
			$current->add($nest[$i]['real_open']);
			$defunct->copy_to($current);
		}
	}
	
	protected function doLinks($text, $callback_for_the_bits_outside) {
		$offset = 0;
		$output = '';
		
		// Find all links
		preg_match_all('@((?:https?://|ftp://|irc://)[^\s<>()"]+?(?:\([^\s<>()"]*?\)[^\s<>()"]*?)*)((?:\s|<|>|"|\.||\]|!|\?|,|&#44;|&quot;)*(?:[\s<>()"]|$))@u', $text, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);
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
			$output .= $before . '<a rel="new nofollow" target="_blank" href="' . htmlspecialchars($match[1][0]) . '">' .
				htmlspecialchars($match[1][0]) . '</a>' . $after;
		
			// Increment offset
			$offset = $match[0][1] + strlen($match[0][0]);
		}
		
		unset($before, $after, $match);
		
		// Do the very last bit
		$end = substr($text, $offset);
		if ($callback_for_the_bits_outside !== false)
			$end = call_user_func($callback_for_the_bits_outside, $end);
		$output .= $end;
		
		return $output;
	}

	protected function doText($text) {
		$text = htmlspecialchars($text);
		
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
				$text .= $this->formatText($node);
			}
		}
		if ($text === '')
			return $tree->markup['open_token'] . $tree->markup['close_token']; // Empty. Example: "****". Treat literally?
		if ($tree->markup) {
			if (isset($tree->markup['callback']))
				$text = call_user_func($tree->markup['callback'], $text);
			return $tree->markup['format'][0] . $text . $tree->markup['format'][1];
		}
		return $text;
	}
}

