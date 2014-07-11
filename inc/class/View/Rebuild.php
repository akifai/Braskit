<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

class View_Rebuild extends View {
    protected function get($app, $boardname) {
        $user = do_login($app);

        $board = new Board($boardname);

        set_time_limit(0);

        $board->rebuildAll();

        redirect($board->path('index.html'));
    }
}

/* vim: set ts=4 sw=4 sts=4 et: */
