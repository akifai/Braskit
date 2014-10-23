<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit\View;

use Braskit\Board;
use Braskit\View;

class Rebuild extends View {
    protected function get($app, $boardname) {
        $user = $app['auth']->authenticate();

        $board = new Board($boardname);

        set_time_limit(0);

        $board->rebuildAll();

        return $this->redirect($board->path('index.html'));
    }
}
