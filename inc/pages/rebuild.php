<?php
defined('TINYIB') or exit;

function rebuild_get($url, $boardname) {
	$user = do_login($url);

	$board = new Board($boardname);

	ignore_user_abort(true);

	// Rebuild all threads
	$threads = $board->getAllThreads();
	foreach ($threads as $thread)
		$board->rebuildThread($thread['id']);

	// Rebuild all indexes
	$board->rebuildIndexes();

	redirect($board->path('index.html'));
}
