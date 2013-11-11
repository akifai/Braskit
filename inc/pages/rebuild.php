<?php
defined('TINYIB') or exit;

function rebuild_get($url, $boardname) {
	$user = do_login($url);

	$board = new Board($boardname);

	set_time_limit(0);

	$board->rebuildAll();

	redirect($board->path('index.html'));
}
