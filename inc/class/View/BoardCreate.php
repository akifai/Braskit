<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

class View_BoardCreate extends View {
    protected function post($app) {
        $user = do_login('/manage');

        $app['csrf']->check();

        $param = $app['param'];

        $boardname = $param->get('path');
        $title = $param->get('title');

        $board = new Board($boardname, false);
        $board->create($title);

        diverge('/manage');
    }
}

/* vim: set ts=4 sw=4 sts=4 et: */
