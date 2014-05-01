<?php

class View_Page extends View {
	protected function get($app, $boardname, $page = 0) {
		$user = do_login($app);

		$board = new Board($boardname);

		$offset = $page * $board->config->threads_per_page;

		$threads = $board->getIndexThreads($offset, true);

		// get number of pages for the page nav
		$maxpage = $board->getMaxPage($board->countThreads());

		if ($page && !count($threads)) {
			// no threads on this page, redirect to page 0
			redirect($board->path('', true));
			return;
		}

		return $board->render('page.html', array(
			'admin' => true,
			'board' => $board,
			'maxpage' => $maxpage,
			'pagenum' => $page,
			'threads' => $threads,
		));
	}
}
