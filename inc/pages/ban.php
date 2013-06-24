<?php
defined('TINYIB') or exit;

/**
 * @todo Find domains and list them.
 */
function ban_get($req, $boardname) {
	$user = do_login($req);
	$board = new Board($boardname);

	$id = param('id');
	$post = $board->getPost($id);

	if (!$post)
		throw new Exception("No such post.");

	$reason = create_ban_message($post);

	echo render('ban.html', array(
		'admin' => true,
		'ajax' => $req->api,
		'board' => $board,
		'post' => $post,
		'reason' => $reason,
	));
}
