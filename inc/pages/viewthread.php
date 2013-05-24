<?php
defined('TINYIB') or exit;

function viewthread_get($url, $boardname, $id) {
	$user = do_login($url);

	$board = new Board($boardname);

	$posts = $board->postsInThread($id);

	if (!$posts) {
		// thread doesn't exist
		diverge("/{$board}/index.html");
		return;
	}

	$twig = $board->getTwig();

	echo render('thread.html', array(
		'admin' => true,
		'board' => $board,
		'posts' => $posts,
		'thread' => $id,
	), $twig);
}
