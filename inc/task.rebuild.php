<?php
defined('TINYIB_BOARD') or exit;

function rebuild_get() {
	$loggedin = check_login();

	if (!$loggedin) {
		redirect(get_script_name().'?task=login&nexttask=rebuild');
		return;
	}


	$redir_after = true;

	if (function_exists('fastcgi_finish_request')) {
		$redir_after = false;

		redirect(expand_path('index.html'));
		fastcgi_finish_request();
	} else {
		ignore_user_abort(true);
	}

	// Rebuild all threads
	$threads = allThreads();
	foreach ($threads as $thread)
		rebuildThread($thread['id']);

	// Rebuild all indexes
	rebuildIndexes();

	if ($redir_after)
		redirect(expand_path('index.html'));
}
