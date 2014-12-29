<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit\View;

use Braskit\Board;
use Braskit\View;

class Thread extends View {
    public function get($app, $boardname, $id) {
        $user = $app['auth']->authenticate();

        $board = new Board($boardname);

        $posts = $board->postsInThread($id, true);

        if (!$posts) {
            // thread doesn't exist
            return $this->diverge("/{$board}/index.html");
        }

        return $this->response->setContent($board->render('thread.html', array(
            'admin' => true,
            'board' => $board,
            'posts' => $posts,
            'thread' => $id,
        )));
    }
}
