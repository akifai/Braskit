<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit\View;

use Braskit\Board;
use Braskit\Error;
use Braskit\View;

/**
 * @todo Find domains and list them.
 */
class Ban extends View {
    protected function get($app, $boardname) {
        $user = $app['auth']->authenticate();
        $board = new Board($boardname);

        $id = $app['param']->get('id');
        $post = $board->getPost($id);

        if (!$post)
            throw new Error("No such post.");

        $reason = create_ban_message($post);

        return $this->render('ban.html', array(
            'admin' => true,
            'board' => $board,
            'post' => $post,
            'reason' => $reason,
        ));
    }
}
