<?php
defined('TINYIB') or exit;

function rebuild_get($url) {
	$user = do_login($url);

	$boardname = param('board');
	$board = new Board($boardname);

	$redir_after = true;

	if (function_exists('fastcgi_finish_request')) {
		$redir_after = false;

		redirect(expand_path('index.html'));
		fastcgi_finish_request();
	} else {
		ignore_user_abort(true);
	}

	// Rebuild all threads
	$threads = $board->getAllThreads();
	foreach ($threads as $thread)
		$board->rebuildThread($thread['id']);

	// Rebuild all indexes
	$board->rebuildIndexes();

	if ($redir_after)
		redirect($board->path('index.html'));
}
