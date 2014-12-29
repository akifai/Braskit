<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit\View;

use Braskit\View;

class Logout extends View {
    public function get($app) {
        $app['auth']->logout();

        return $this->diverge('/login');
    }
}
