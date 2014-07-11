<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

class View_Thread extends View {
    protected function get($app, $boardname, $id) {
        $user = do_login($app);

        $board = new Board($boardname);

        $posts = $board->postsInThread($id, true);

        if (!$posts) {
            // thread doesn't exist
            diverge("/{$board}/index.html");
            return;
        }

        return $board->render('thread.html', array(
            'admin' => true,
            'board' => $board,
            'posts' => $posts,
            'thread' => $id,
        ));
    }
}

/* vim: set ts=4 sw=4 sts=4 et: */
