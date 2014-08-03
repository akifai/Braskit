<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit\View;

use Braskit\View;

class Logout extends View {
    protected function get($app) {
        if (isset($app['session']['login'])) {
            unset($app['session']['login']);
        }

        diverge('/login');
    }
}

/* vim: set ts=4 sw=4 sts=4 et: */
