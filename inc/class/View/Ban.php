<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

/**
 * @todo Find domains and list them.
 */
class View_Ban extends View {
    protected function get($app, $boardname) {
        $user = do_login($app);
        $board = new Board($boardname);

        $id = $app['param']->get('id');
        $post = $board->getPost($id);

        if (!$post)
            throw new Exception("No such post.");

        $reason = create_ban_message($post);

        return $this->render('ban.html', array(
            'admin' => true,
            'board' => $board,
            'post' => $post,
            'reason' => $reason,
        ));
    }
}

/* vim: set ts=4 sw=4 sts=4 et: */
