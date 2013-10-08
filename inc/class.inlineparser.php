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

class InlineParser {
	protected $raw = '';
	protected $parsed = '';
	protected $stack = array();
	protected $tree;
	protected $markup = array(
		array(
			'token' => array('*', '_'),
			'format' => array('<em>', '</em>'),
			'children' => true
		),
		array(
			'token' => array('**', '__'),
			'format' => array('<strong>', '</strong>'),
			'children' => true
		),
		array(
			'token' => array('`+'),
			'format' => array('<code>', '</code>'),
			'callback' => 'trim',
			'children' => false
		)
	);
	
	public function __construct($text) {
		$this->raw = $text;
		$this->parse();
	}

	public function getParsed() {
		return $this->parsed;	
	}
	
	protected function parse() {
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
			foreach ($markup['token'] as $token) {
				$regex[] = str_replace('\+', '+', preg_quote($token, '/'));
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
		foreach ($tokens as $token) {
			if (strlen($token) > 1 && substr($token, -1) == '+') {
				if (str_repeat(substr($token, 0, -1), strlen($part)) === $part)
					return true;
			} else {
				if ($token === $part)
					return true;
			}
		}
		return false;
	}

	protected function isOpen($nest, $token, $check_other_tokens = false) {
		foreach ($nest as $markup) {
			if (!$check_other_tokens) {
				if ($markup['real'] === $token)
					return true;
			} else {
				if ($this->isToken($markup['token'], $token))
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
				if ($this->isToken($markup['token'], $part)) {
					$this_markup = $markup;
					$this_markup['real'] = $part;
				}
			}
			if ($this_markup !== false) {
				$nest_current = end($nest);
				if ($nest_current && !$nest_current['children'] && $nest_current['real'] !== $part) {
					// We are inside a block which doesn't accept children. Treat literally.
					$current->add($part);
				} elseif ($nest_current && $nest_current['real'] === $part) {
					// Correct nesting.
					// Move back up a layer.
					array_pop($nest);
					$current = $current->parent;
				} elseif ($this->isOpen($nest, $part)) {
					// Invalid. You're closing a tag out of order.
					for ($x = count($nest) - 1; $x >= 0; $x--) {
						$markup = array_pop($nest);
						if ($markup['real'] === $part)
							break;
						$defunct = $current;
						// Go back up a layer.
						$current = $defunct->parent;
						// Remove the invalid layer from the tree.
						$current->pop();
						// Move the contents of the invalid layer into its parent.
						$current->add($markup['real']);
						$defunct->copy_to($current);
					}
					// Go back up a step again.
					$current = $current->parent;
				} else {
					if ($this->isOpen($nest, $part, true)) {
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
			$current->add($nest[$i]['real']);
			$defunct->copy_to($current);
		}
	}
	
	protected function doText($text) {		
		$text = htmlspecialchars($text);
		
		// TODO >>XX links
		// TODO: Obviously this will be better in the future:
		$text = preg_replace('@(https?://[^\s]*)@', '<a href="$1">$1</a>', $text);
		
		return $text;
	}
	
	protected function formatTree($tree) {
		$text = '';
		foreach ($tree->nodes as $node) {
			if ($node instanceof InlineParseTree) {
				$text .= $this->formatTree($node);
			} else {
				$text .= $this->doText($node);
			}
		}
		if ($text === '')
			return $tree->markup['real'] . $tree->markup['real']; // Empty. Example: "****". Treat literally?
		if ($tree->markup) {
			if (isset($tree->markup['callback']))
				$text = call_user_func($tree->markup['callback'], $text);
			return $tree->markup['format'][0] . $text . $tree->markup['format'][1];
		}
		return $text;
	}
}

/*
function parse_inline($text) {
	$parser = new InlineParser($text);
	return $parser->getParsed();
}
*/
