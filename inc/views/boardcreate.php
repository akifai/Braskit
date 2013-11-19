<?php

class View_BoardCreate extends View {
	protected function post($url) {
		$user = do_login('/manage');
		do_csrf($url);

		$boardname = param('path');
		$title = param('title');

		$board = new Board($boardname, false);
		$board->create($title);

		diverge('/manage');
	}
}
