<?php

class View_Thread extends View {
	protected function get($url, $boardname, $id) {
		$user = do_login($url);

		$board = new Board($boardname);

		$posts = $board->postsInThread($id, true);

		if (!$posts) {
			// thread doesn't exist
			diverge("/{$board}/index.html");
			return;
		}

		$twig = $board->getTwig();

		return $twig->render('thread.html', array(
			'admin' => true,
			'board' => $board,
			'posts' => $posts,
			'thread' => $id,
		), $twig);
	}
}
