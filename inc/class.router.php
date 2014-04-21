<?php

/**
 * An interface for URL routing.
 */
abstract class Router {
	/**
	 * The regex that matched the URL, or false if none matched.
	 *
	 * @var mixed
	 */
	public $regex = false;

	/**
	 * The view associated with the regex that matched the URL, or false if
	 * no regexes mathed.
	 *
	 * @var mixed
	 */
	public $view = false;

	/**
	 * An array of the capture groups found in the URL.
	 *
	 * @var array
	 */
	public $matches = array();

	/**
	 * An array of URL regexes and their associated views.
	 *
	 * @var array
	 */
	protected $routes = array();

	/**
	 * A method that defines the valid routes.
	 */
	abstract protected function setRoutes();

	/**
	 * Matches the given URL with the list of routes. If a route is found,
	 * its associated view (a class name) is assigned to the $view property.
	 *
	 * @param string $url        The URL we're trying to match routes with.
	 * @param callable $notfound A callback to execute if a route cannot be
	 *                           found.
	 *
	 * @throws Exception if a route cannot be found
	 */
	public function __construct($url, $notfound = false) {
		$this->setRoutes();

		foreach ($this->routes as $regex => $view) {
			$regex = '@^'.$regex.'$@';

			if (preg_match($regex, $url, $matches)) {
				$this->regex = $regex;
				$this->view = $view;
				$this->matches = $matches;

				return;
			}
		}

		if (is_callable($notfound)) {
			call_user_func($notfound);
			return;
		}

		throw new Exception('Unknown task.');
	}
}

/**
 * Routes for ajax.php & board.php.
 */
class Router_Main extends Router {
	public function setRoutes() {
		// Regex for boards
		$board_re = '('.Board::BOARD_RE.')';

		// Regex for "safe numbers" - 1-99999999
		$num_re = '([1-9]\d{0,8})';

		$this->routes = array(
			// User actions
			"/$board_re/post" => 'View_Post',
			"/$board_re/delete" => 'View_Delete',
			"/$board_re/report" => 'View_Report',

			// Mod view
			"/$board_re/(?:$num_re(?:\\.html)?|index\\.html)?" => 'View_Page',
			"/$board_re/res/$num_re(?:\\.html)?" => 'View_Thread',

			// Mod board actions
			"/$board_re/ban" => 'View_Ban',
			"/$board_re/config" => 'View_Config',
			"/$board_re/edit" => 'View_BoardEdit',
			"/$board_re/rebuild" => 'View_Rebuild',

			// Mod global actions
			'/bans' => 'View_Bans',
			'/config' => 'View_Config',
			'/login' => 'View_Login',
			'/logout' => 'View_Logout',
			'/manage' => 'View_Manage',
			'/reports' => 'View_Reports',

			'/create_board' => 'View_BoardCreate',
			'/users(?:/(\w+))?' => 'View_Users',
		);
	}
}

/**
 * Routes for the installer.
 */
class Router_Install extends Router {
	public function setRoutes() {
		$this->routes = array(
			// step 1
			'/' => 'View_Install_Start',

			// step 2
			'/config' => 'View_Install_Config',
			'/get_config' => 'View_Install_Download',
			'/restart' => 'View_Install_Restart',

			// step 3
			'/finish' => 'View_Install_Finish',
		);
	}
}

abstract class Path {
	/**
	 * Creates a URL, optionally with parameters.
	 *
	 * Currently, the returned URL is expected to build upon the URL of the
	 * script (e.g. http://example.com/board.php). This behaviour is bound to
	 * change.
	 *
	 * @param string $path The path to create a URL for.
	 * @param mixed $parameters A
	 */
	abstract public function create($path, $params);

	/**
	 * Retrieves the current path.
	 *
	 * @return string The current path.
	 */
	abstract public function get();
}

/**
 * Path subclass for query string-based routing and URLs. Will work
 * in any setup, but creates ugly URLs.
 */
class Path_QueryString extends Path {
	protected $request;

	public function __construct(Request $request) {
		$this->request = $request;
	}

	public function create($task, $params) {
		$path = '?'.$task;

		if (!is_array($params)) {
			return $path;
		}

		$arg_string = '';

		foreach ($params as $name => $value) {
			if (is_array($value)) {
				foreach ($value as $sk => $sv) {
					if ($sk === (int)$sk) {
						$sk = '';
					}

					$arg_string .= "&{$name}[{$sk}]={$sv}";
				}
			} else {
				$arg_string .= "&{$name}={$value}";
			}
		}

		return $path.$arg_string;
	}

	public function get() {
		$query = &$this->request->server['QUERY_STRING'];

		if (!isset($query) || substr($query, 0, 1) !== '/') {
			// the query string is either invalid or not defined
			return '/';
		}

		$pos = strpos($query, '&');

		if ($pos !== false) {
			// ignore other GET values
			$url = substr($query, 0, $pos);
		} else {
			// we can use the whole query string
			$url = $query;
		}

		return $url;
	}
}
