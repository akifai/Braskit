<?php
defined('TINYIB') or exit;

function manage_get($url) {
	$user = do_login($url);
	$boards = getAllBoards();

	echo render('manage.html', array(
		'admin' => true,
		'boards' => $boards,
		'user' => $user,
	));
}
