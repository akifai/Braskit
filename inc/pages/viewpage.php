<?php
defined('TINYIB') or exit;

function viewpage_get($url, $boardname, $page = 0) {
	$loggedin = check_login();

	if (!$loggedin) {
		redirect(get_script_name().'?task=login&nexttask=manage');
		return;
	}

	$board = new Board($boardname);

	$offset = $page * 10;

	// TODO: Fix this so we don't have to get all threads at once
	$threads = $board->getIndexThreads($offset);

	// get number of pages for the page nav
	$maxpage = get_page_count(count($threads)) - 1;

	if ($page && !count($threads)) {
		// no threads on this page, redirect to page 0
		redirect(get_script_name().'?task=manage');
		return;
	}

	echo render('page.html', array(
		'admin' => true,
		'board' => $board,
		'maxpage' => $maxpage,
		'pagenum' => $page,
		'threads' => $threads,
	));
}
