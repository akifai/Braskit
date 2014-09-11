<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit\View\Install;

use Braskit\View;

class Download extends View {
    protected function get($app) {
        if (!$app['session']->has('install_config')) {
            // No config stored
            header('HTTP/1.1 404 Not Found');

            // this needs to show a message usable for command line users
            // as well as for browser users
            printf("[<a href=\"%s\">Click</a>]<br>\n\n",
                $app['request']->getScriptName()
            );

            printf("Go to %s://%s%s to start the installation.\n",
                $app['request']->getScheme(),
                $app['request']->getHost(),
                $app['request']->getScriptName()
            );

            return;
        }

        // offer the config as a download
        header('Content-Disposition: attachment; filename=config.php');
        echo $app['session']->get('install_config');
    }
}

/* vim: set ts=4 sw=4 sts=4 et: */
