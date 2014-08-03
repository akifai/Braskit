<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit\Router;

use Braskit\Router;

/**
 * Routes for the installer.
 */
class Install extends Router {
    protected $prefix = 'Braskit\\View\\Install\\';

    public function setRoutes() {
        $this->routes = array(
            // step 1
            '/' => 'Start',

            // step 2
            '/config' => 'Config',
            '/get_config' => 'Download',
            '/restart' => 'Restart',

            // step 3
            '/finish' => 'Finish',
        );
    }
}

/* vim: set ts=4 sw=4 sts=4 et: */
