<?php
defined('TINYIB') or exit;

function edit_board_get($url, $boardname) {
	$user = do_login($url);

	$board = new Board($boardname);

	echo render('edit_board.html', array(
		'admin' => true,
		'board' => $board,
	));
}

function edit_board_post($url, $boardname) {
	$user = do_login($url);
	do_csrf($url);

	$board = new Board($boardname);

	$name = param('name');
	$title = param('title');
	$minlevel = param('minlevel');
	$rebuild = param('rebuild');

	$board->editSettings($title, $minlevel);

	set_time_limit(0);
	ignore_user_abort(true);

	if ($name !== '' && $name !== (string)$board) {
		$board->rename($name);
	}

	if ($rebuild)
		$board->rebuildAll();

	// the board might have changes names
	redirect($board->path('edit', true));
}
