<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit\View;

use Braskit\Board;
use Braskit\View;

class BoardEdit extends View {
    public function get($app, $boardname) {
        $user = $app['auth']->authenticate();

        $board = new Board($boardname);

        return $this->render('edit_board.html', array(
            'admin' => true,
            'board' => $board,
        ));
    }

    public function post($app, $boardname) {
        $user = $app['auth']->authenticate();

        $app['csrf']->check();

        $param = $app['param'];

        $board = new Board($boardname);

        $name = $param->get('name');
        $title = $param->get('title');
        $minlevel = $param->get('minlevel');
        $rebuild = $param->get('rebuild');

        $board->editSettings($title, $minlevel);

        set_time_limit(0);

        if ($name !== '' && $name !== (string)$board) {
            $board->rename($name);
        }

        if ($rebuild)
            $board->rebuildAll();

        // the board might have changes names
        return $this->redirect($board->path('edit', true));
    }
}
