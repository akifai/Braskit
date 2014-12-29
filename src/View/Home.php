<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit\View;

use Braskit\View;

class Home extends View {
    public function get() {
        return $this->diverge('/login');
    }
}
