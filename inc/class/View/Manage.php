<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit\View;

use Board; // todo
use Braskit\View;

class Manage extends View {
    protected function get($app) {
        $user = do_login($app);

        $boards = array();

        foreach ($app['db']->getAllBoards() as $board) {
            $boards[$board['name']] = new Board($board['name']);
        }

        // gets the latest posts from all boards
        $posts = $app['db']->getLatestPosts($app['config']->latest_posts_count, true);

        // give each post a board object
        foreach ($posts as &$post) {
            $post->board = $boards[$post->board];
        }

        return $this->render('manage.html', array(
            'admin' => true,
            'posts' => $posts,
            'user' => $user,
        ));
    }
}

/* vim: set ts=4 sw=4 sts=4 et: */
