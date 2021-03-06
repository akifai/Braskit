<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit\View;

use Braskit\Board;
use Braskit\View;

class Page extends View {
    public function get($app, $boardname, $page = 0) {
        $user = $app['auth']->authenticate();

        $board = new Board($boardname);

        $offset = $page * $board->config->get('threads_per_page');

        $threads = $board->getIndexThreads($offset, true);

        // get number of pages for the page nav
        $maxpage = $board->getMaxPage($board->countThreads());

        if ($page && !count($threads)) {
            // no threads on this page, redirect to page 0
            return $this->redirect($board->path('', true));
        }

        return $this->response->setContent($board->render('page.html', array(
            'admin' => true,
            'board' => $board,
            'maxpage' => $maxpage,
            'pagenum' => $page,
            'threads' => $threads,
        )));
    }
}
