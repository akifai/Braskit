<?php
defined('TINYIB') or exit;

require_once 'inc/lib/Twig/Autoloader.php';
Twig_Autoloader::register();

class PlainIB_Twig_Extension extends Twig_Extension {
	public function getFunctions() {
		$functions = array(
			new Twig_SimpleFunction('csrf', 'get_csrf_token'),
			new Twig_SimpleFunction('less', 'get_less_path'),
			new Twig_SimpleFunction('path', 'expand_path'),
			new Twig_SimpleFunction('self', 'get_script_name'), // deprecated
			new Twig_SimpleFunction('filename', 'shorten_filename'),
		);

		return $functions;
	}

	public function getGlobals() {
		global $config, $debug;

		$globals = array('self' => get_script_name());

		if (isset($config))
			$globals['config'] = $config;

		if (isset($debug))
			$globals['debug'] = $debug;

		return $globals;
	}

	public function getName() {
		return 'plainib';
	}
}

class PlainIB_Twig_Loader extends Twig_Loader_Filesystem {
	// https://developer.mozilla.org/en-US/docs/HTML/Block-level_elements
	protected static $no_whitespace_elements = array(
		// <head> elements
		// no <script> because that could be used inline and affect
		// whitespace which is intended to be there
		'!doctype', 'html', 'head', 'title', 'link', 'meta', 'style',

		// various block elements
		'h[1-6r]', 'blockquote', 'div', 'br', 'form', 'p', 'address',
		'center', 'plaintext', 'isindex', 'pre', 'fieldset', 'noscript',

		// List elements
		'd[ltd]', '[ou]l', 'li',

		// Table elements
		'table', 'tbody', 'thead', 'tfoot', 't[hdr]', 'caption',
		'col', 'colgroup',

		// HTML5
		'article', 'aside', 'audio', 'canvas', 'figcaption', 'figure',
		'footer', 'header', 'hgroup',  'output', 'section', 'video',
	);

	protected static $whitespace_chars = array("\r", "\n", "\t");

	protected static function compileWhitespaceRegex() {
		$joined = implode('|', self::$no_whitespace_elements);
		$regex = '@\s*(</?(?:'.$joined.')(?: .*?)?>)\s*@';

		return $regex;
	}

	public function getSource($name) {
		$regex = self::compileWhitespaceRegex();
		$source = file_get_contents($this->findTemplate($name));

		// replace newlines and tabs with spaces
		$source = str_replace(self::$whitespace_chars, ' ', $source);

		// remove whitespace before and after block elements
		$source = preg_replace($regex, '\1', $source);

		// remove whitespace at the beginning and the end of the file
		$source = trim($source);

		do {
			// remove double whitespaces
			$source = str_replace('  ', ' ', $source, $count);
		} while ($count);

		return $source;
	}
}
