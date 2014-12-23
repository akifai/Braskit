<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit\View;

use Braskit\Board;
use Braskit\View;

class Manage extends View {
    protected function get($app) {
        $user = $app['auth']->authenticate();

        $boards = array();

        foreach ($app['db']->getAllBoards() as $board) {
            $boards[$board['name']] = new Board($board['name']);
        }

        // gets the latest posts from all boards
        $count = $app['config']->getPool('global')->get('latest_posts_count');
        $posts = $app['db']->getLatestPosts($count, true);

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
