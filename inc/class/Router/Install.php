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
    public function setRoutes() {
        $this->routes = array(
            // step 1
            '/' => 'View_Install_Start',

            // step 2
            '/config' => 'View_Install_Config',
            '/get_config' => 'View_Install_Download',
            '/restart' => 'View_Install_Restart',

            // step 3
            '/finish' => 'View_Install_Finish',
        );
    }
}

/* vim: set ts=4 sw=4 sts=4 et: */
