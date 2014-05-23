<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

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

				// first "match" is always the subject string
				array_shift($this->matches);

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
