<?php

class View_BoardCreate extends View {
	protected function post($app) {
		$user = do_login('/manage');
		do_csrf($app);

		$param = $app['param'];

		$boardname = $param->get('path');
		$title = $param->get('title');

		$board = new Board($boardname, false);
		$board->create($title);

		diverge('/manage');
	}
}
