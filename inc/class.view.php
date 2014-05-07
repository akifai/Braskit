<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

abstract class View {
	/**
	 * App instance
	 */
	public $app;

	/**
	 * The request body.
	 *
	 * @var string
	 */
	public $responseBody = '';

	/**
	 * @todo Avoid globals.
	 */
	public function __construct(App $app) {
		global $app;

		$this->app = $app;

		$request = $app['request'];

		if (!$request->method) {
			throw new LogicException('View executed outside of HTTP context.');
		}

		$verb = $request->method === 'POST' ? 'post' : 'get';
		$method = array($this, $verb);

		if (!is_callable($method)) {
			$this->methodNotAllowed();
		}

		$args = $app['router']->matches;

		// set the first argument to $app
		array_unshift($args, $app);

		$this->responseBody = call_user_func_array($method, $args);
	}

	protected function csrfScreen() {
		return $this->render('csrf.html');
	}

	private function methodNotAllowed() {
		header('HTTP/1.0 405 Method Not Allowed');
		throw new Exception('Method not allowed.');
	}

	protected function render($template, $args = array()) {
		return $this->app['template']->render($template, $args);
	}
}
