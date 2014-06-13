<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

class Braskit_Twig_Extension extends Twig_Extension {
	public function getFunctions() {
		$functions = array(
			new Twig_SimpleFunction('js', 'get_js'),
			new Twig_SimpleFunction('path', 'expand_path'),
			new Twig_SimpleFunction('apipath', 'expand_api_path'),
		);

		return $functions;
	}

	public function getFilters() {
		$filters = array(
			new Twig_SimpleFilter('json_decode', 'json_decode'),
		);

		return $filters;
	}

	public function getGlobals() {
		global $app;

		$globals = array(
			'app' => $app,
			'_base' => 'base/main.html',
			'self' => $app['request']->getScriptName(),
			'style' => Style::getObject(),
		);

		if (defined('TINYIB_BASE_TEMPLATE')) {
			$globals['_base'] = TINYIB_BASE_TEMPLATE;
		}

		return $globals;
	}

	public function getName() {
		return 'braskit';
	}
}

class Braskit_Twig_Loader extends Twig_Loader_Filesystem {
	// https://developer.mozilla.org/en-US/docs/HTML/Block-level_elements
	protected $noWhiteSpaceElements = array(
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
		'footer', 'header', 'hgroup', 'output', 'section', 'video',
	);

	protected $whiteSpaceChars = array("\r", "\n", "\t");

	protected function getWhitespaceRegex() {
		$joined = implode('|', $this->noWhiteSpaceElements);
		$regex = '@\s*(</?(?:'.$joined.')(?: .*?)?>)\s*@';

		return $regex;
	}

	public function getSource($name) {
		$name = $this->findTemplate($name);
		$source = file_get_contents($name);

		if (strrpos($name, '.html') === strlen($name)-5) {
			$source = $this->trimHTMLWhiteSpace($source);
		}

		return $source;
	}

	public function trimHTMLWhiteSpace($source) {
		$regex = $this->getWhiteSpaceRegex();

		// replace newlines and tabs with spaces
		$source = str_replace($this->whiteSpaceChars, ' ', $source);

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