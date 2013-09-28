<?php
defined('TINYIB') or exit;

/**
 * @todo Find domains and list them.
 */
function ban_get($url, $boardname) {
	$user = do_login($url);
	$board = new Board($boardname);

	$id = param('id');
	$post = $board->getPost($id);

	if (!$post)
		throw new Exception("No such post.");

	$reason = create_ban_message($post);

	echo render('ban.html', array(
		'admin' => true,
		'board' => $board,
		'post' => $post,
		'reason' => $reason,
	));
}
