<?php

class View_BoardCreate extends View {
	protected function post($url) {
		$user = do_login('/manage');
		do_csrf($url);

		$param = $this->app['param'];

		$boardname = $param->get('path');
		$title = $param->get('title');

		$board = new Board($boardname, false);
		$board->create($title);

		diverge('/manage');
	}
}
