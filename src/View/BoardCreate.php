<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit\View;

use Braskit\Board;
use Braskit\View;

class BoardCreate extends View {
    protected function post($app) {
        $user = do_login('/manage');

        $app['csrf']->check();

        $param = $app['param'];

        $boardname = $param->get('path');
        $title = $param->get('title');

        $board = new Board($boardname, false);
        $board->create($title);

        return $this->diverge('/manage');
    }
}

/* vim: set ts=4 sw=4 sts=4 et: */
