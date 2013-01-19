<?php
defined('TINYIB_BOARD') or exit;

function manage_get() {
	list($loggedin, $isadmin) = manageCheckLogIn();

	if (!$loggedin) {
		redirect(get_script_name().'?task=login&nexttask=manage');
		return;
	}


	//
	// Show thread
	//

	if (isset($_GET['thread'])) {
		// Show thread
		$posts = postsInThreadByID($_GET['thread']);

		if (!$posts) {
			// thread is non-existent, redirect to page 0
			redirect(get_script_name().'?task=manage');
			return;
		}

		echo render('thread.html', array(
			'admin' => true,
			'posts' => $posts,
			'thread' => $_GET['thread'],
		));

		return;
	}


	//
	// Show index
	//

	// Page number
	$page = isset($_GET['page']) ? $_GET['page'] : 0;
	$offset = $page * 10;

	// TODO: Fix this so we don't have to get all threads at once
	$threads = get_index_threads();
	$pagecount = floor(count($threads) / 10);

	if ($page && !isset($threads[$offset])) {
		// no threads at offset, redirect to page 0
		redirect(get_script_name().'?task=manage');
		return;
	}

	$threads = array_splice($threads, $offset, 10);

	echo render('page.html', array(
		'admin' => true,
		'pagenum' => $page,
		'pagecount' => $pagecount,
		'threads' => $threads,
	));
}
