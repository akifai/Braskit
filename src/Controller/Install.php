<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit\Controller;

use Braskit\Controller;
use Braskit\Router\Install as Router;

/**
 * Controller for install.php
 */
class Install extends Controller {
    const CONTENT_TYPE = 'Content-Type: text/html; charset=UTF-8';

    public function run() {
        if (!ob_get_level()) {
            ob_start();
        }

        $app = $this->app;

        $config = $this->app['path.root'].'/config.php';

        if (file_exists($config) && !$app['session']->has('installer')) {
            header('HTTP/1.1 403 Forbidden');

            echo 'Braskit is already installed. ',
                'To re-run the installer, move or delete config.php.';

            return;
        }

        // let us download the session-stored config without using cookies
        $app['session']->setOptions(['use_only_cookies' => false]);

        $app['session']->set('installer', true);

        header(self::CONTENT_TYPE);

        echo $app['view']->responseBody;
    }

    public function getRouter() {
        return new Router($this->app['url']->get());
    }

    public function exceptionHandler(\Exception $e) {
        // too lazy to bother with this now
        var_dump($e);
    }
}

/* vim: set ts=4 sw=4 sts=4 et: */
