<?php
defined('TINYIB') or exit;

function create_board_post($url) {
	$user = do_login($url);

	$boardname = param('path');
	$title = param('title');

	$board = new Board($boardname, false);
	$board->create($title);

	diverge('/manage');
}
