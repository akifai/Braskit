<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit\View;

use Braskit\Board;
use Braskit\View;

class BoardsView extends View {
    protected function get($app) {
        $user = do_login($app);

        $boards = [];

        foreach ($app['db']->getAllBoards() as $board) {
            $boards[$board['name']] = new Board($board['name']);
        }

        $this->setVar('boards', $boards);

        return $this->render('boards.html');
    }

    protected function post($app) {
        $user = do_login($app);

        $app['csrf']->check();
        $param = $app['param'];

        $boardname = $param->get('name');
        $title = $param->get('title');

        $board = new Board($boardname, false);
        $board->create($title);

        return $this->diverge('/boards');
    }
}

/* vim: set ts=4 sw=4 sts=4 et: */
