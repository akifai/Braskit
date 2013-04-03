<?php
defined('TINYIB') or exit;

function viewthread_get($url, $boardname, $id) {
	$loggedin = check_login();

	if (!$loggedin) {
		redirect(get_script_name().'?task=login&nexttask=manage');
		return;
	}

	$board = new Board($boardname);

	$posts = $board->postsInThread($id);

	if (!$posts) {
		// thread doesn't exist
		redirect(get_script_name().'?task=manage&board='.$board);
		return;
	}

	echo render('thread.html', array(
		'admin' => true,
		'board' => $board,
		'posts' => $posts,
		'thread' => $id,
	));
}
