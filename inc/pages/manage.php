<?php
defined('TINYIB') or exit;

function manage_get($url) {
	$user = do_login($url);

	$boards = array();
	foreach (getAllBoards() as $board)
		$boards[$board['name']] = new Board($board['name']);

	// gets the latest posts from all boards
	$posts = getLatestPosts(array_keys($boards));

	// give each post a board object
	foreach ($posts as &$post)
		$post['board'] = $boards[$post['board']];

	echo render('manage.html', array(
		'admin' => true,
		'posts' => $posts,
		'user' => $user,
	));
}
