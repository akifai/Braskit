<?php
defined('TINYIB') or exit;

function manage_get() {
	$loggedin = check_login();

	if (!$loggedin) {
		redirect(get_script_name().'?task=login&nexttask=manage');
		return;
	}

	$boardname = param('board');
	$thread = param('thread');
	$page = param('page');

	$board = new Board($boardname);

	// Show thread
	if ($thread) {
		$posts = postsInThreadByID($board, $_GET['thread']);

		if (!$posts) {
			// thread is non-existent, redirect to page 0
			redirect(get_script_name().'?task=manage');
			return;
		}

		echo render('thread.html', array(
			'admin' => true,
			'board' => $board,
			'posts' => $posts,
			'thread' => $_GET['thread'],
		));

		return;
	}

	// Show index

	// Page number
	$page = isset($_GET['page']) ? $_GET['page'] : 0;
	$offset = $page * 10;

	// TODO: Fix this so we don't have to get all threads at once
	$threads = $board->getIndexThreads($offset);
	$pagecount = floor($board->countThreads() / 10);

	if ($page && !count($threads)) {
		// no threads on this page, redirect to page 0
		redirect(get_script_name().'?task=manage');
		return;
	}

	echo render('page.html', array(
		'admin' => true,
		'board' => $board,
		'pagenum' => $page,
		'pagecount' => $pagecount,
		'threads' => $threads,
	));
}
