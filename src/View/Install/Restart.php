<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit\View\Install;

use Braskit\View;

class Restart extends View {
    protected function get($app) {
        $app['session']->remove('install_config');

        return $this->diverge('/');
    }
}

/* vim: set ts=4 sw=4 sts=4 et: */
