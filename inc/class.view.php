<?php

abstract class View {
	/**
	 * The request body.
	 *
	 * @var string
	 */
	public $responseBody = '';

	/**
	 * @todo Avoid globals.
	 */
	public function __construct(Router $router) {
		global $request;

		if (!$request->method) {
			throw new LogicException('View executed outside of HTTP context.');
		}

		$verb = $request->method === 'POST' ? 'post' : 'get';
		$method = array($this, $verb);

		if (!is_callable($method)) {
			$this->methodNotAllowed();
		}

		$this->responseBody = call_user_func_array($method, $router->matches);
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
