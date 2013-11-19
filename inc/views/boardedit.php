<?php

class View_BoardEdit extends View {
	protected function get($url, $boardname) {
		$user = do_login($url);

		$board = new Board($boardname);

		return $this->render('edit_board.html', array(
			'admin' => true,
			'board' => $board,
		));
	}

	protected function post($url, $boardname) {
		$user = do_login($url);
		do_csrf($url);

		$board = new Board($boardname);

		$name = param('name');
		$title = param('title');
		$minlevel = param('minlevel');
		$rebuild = param('rebuild');

		$board->editSettings($title, $minlevel);

		set_time_limit(0);

		if ($name !== '' && $name !== (string)$board) {
			$board->rename($name);
		}

		if ($rebuild)
			$board->rebuildAll();

		// the board might have changes names
		redirect($board->path('edit', true));
	}
}
