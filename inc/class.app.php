<?php
defined('TINYIB') or exit;

abstract class App {
	// callbacks for errors
	public $cb_404 = null;
	public $cb_405 = null;

	protected $dir;
	protected $tasks;
	protected $method;
	protected $url;

	// the value of the match, i.e. which php file and function to run
	protected $match;

	// did the url match anything?
	protected $matched = false;

	// url arguments
	protected $matches = array();

	public function __construct($tasks, $dir) {
		$this->tasks = $tasks;
		$this->dir = $dir;
	}

	public function run() {
		$this->url = $this->get();
		$this->matchRoute();

		if ($this->matched) {
			$this->loadFile();
			$this->getMethod();
			$this->runFunction();
		} else {
			$this->do_404();
		}
	}

	protected function getMethod() {
		if ($_SERVER['REQUEST_METHOD'] === 'POST')
			$this->method = 'post';
		else
			$this->method = 'get';
	}

	protected function matchRoute() {
		foreach ($this->tasks as $regex => $func) {
			if ($this->testMatch($regex)) {
				$this->matched = true;
				$this->match = $func;

				break;
			}
		}
	}

	protected function testMatch($regex) {
		return preg_match("!^{$regex}$!", $this->url, $this->matches);
	}

	protected function loadFile() {
		$path = sprintf('%s/inc/%s/%s.php',
			TINYIB_ROOT, $this->dir, $this->match);

		// load the .php corresponding to the task
		if (file_exists($path)) {
			require($path);
			return;
		}

		throw new Exception("Cannot load {$this->match}.php.");
	}

	protected function runFunction() {
		// run function equivalent to the current http method
		if (function_exists($func = $this->match.'_'.$this->method)) {
			call_user_func_array($func, $this->matches);
			return;
		}
		
		// otherwise try running a "wildcard" function
		if (function_exists($func = $this->match.'_any')) {
			call_user_func_array($func, $this->matches);
			return;
		}

		// the http method is not allowed
		$this->do_405();
	}

	protected function do_404() {
		if (!is_callable($this->cb_404)) {
			header('HTTP/1.1 404 Not Found');
			throw new Exception('Invalid task.');
			return;
		}

		call_user_func($this->cb_404);
	}

	protected function do_405() {
		if (!is_callable($this->cb_405)) {
			header('HTTP/1.1 405 Method Not Allowed');
			throw new Exception('Method not allowed.');
			return;
		}

		call_user_func($this->cb_405);
	}
}

class RouteQueryString extends App {
	// still unsure about how this should work
	public static function create($task, $args) {
		$path = '?'.$task;

		if (!is_array($args))
			return $path;

		$arg_string = '';

		foreach ($args as $name => $value)
			$arg_string .= "&{$name}={$value}";

		return $path.$arg_string;
	}

	public static function get() {
		if (!isset($_SERVER['QUERY_STRING'])
		|| substr($_SERVER['QUERY_STRING'], 0, 1) !== '/') {
			// the query string is either invalid or not defined
			return '/';
		}

		$pos = strpos($_SERVER['QUERY_STRING'], '&');

		if ($pos !== false) {
			// ignore other GET values
			$url = substr($_SERVER['QUERY_STRING'], 0, $pos);
		} else {
			// we can use the whole query string
			$url = $_SERVER['QUERY_STRING'];
		}

		// remove task URL from $_GET
		array_shift($_GET);

		return $url;
	}
}

// FIXME: it's broken
class RoutePathInfo extends App {
	public static function create($task, $args) {
		return get_script_name().$task;
	}

	public static function get() {
		if (!isset($_SERVER['PATH_INFO']))
			return '/';

		return $_SERVER['PATH_INFO'];
	}
}
