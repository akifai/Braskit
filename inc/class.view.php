<?php

abstract class View {
	/**
	 * The request body.
	 *
	 * @var string
	 */
	public $requestBody = '';

	/**
	 * @todo Avoid superglobals.
	 */
	public function __construct(Router $router) {
		if (!isset($_SERVER['REQUEST_METHOD'])) {
			throw new LogicException('View executed outside of HTTP context.');
		}

		$verb = $_SERVER['REQUEST_METHOD'] === 'POST' ? 'post' : 'get';
		$method = array($this, $verb);

		if (!is_callable($method)) {
			$this->methodNotAllowed();
		}

		$this->requestBody = call_user_func_array($method, $router->matches);
	}

	private function methodNotAllowed() {
		header('HTTP/1.0 405 Method Not Allowed');
		throw new Exception('Method not allowed.');
	}

	/**
	 * @todo
	 */
	protected function render() {
		echo call_user_func_array('render', func_get_args());
	}
}
