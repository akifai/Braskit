<?php

/**
 * @todo Find domains and list them.
 */
class View_Ban extends View {
	protected function get($url, $boardname) {
		global $app;

		$user = do_login($url);
		$board = new Board($boardname);

		$id = $app['param']->get('id');
		$post = $board->getPost($id);

		if (!$post)
			throw new Exception("No such post.");

		$reason = create_ban_message($post);

		return $this->render('ban.html', array(
			'admin' => true,
			'board' => $board,
			'post' => $post,
			'reason' => $reason,
		));
	}
}
