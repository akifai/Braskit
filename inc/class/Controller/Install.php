<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

/**
 * Controller for install.php
 */
class Controller_Install extends Controller {
    const CONTENT_TYPE = 'Content-Type: text/html; charset=UTF-8';

    public function run() {
        if (!ob_get_level()) {
            ob_start();
        }

        $app = $this->app;

        $this->globalSetup();

        $config = $this->app['path.root'].'/config.php';

        if (file_exists($config) && !isset($app['session']['installer'])) {
            header('HTTP/1.1 403 Forbidden');

            echo 'Braskit is already installed. ',
                'To re-run the installer, move or delete config.php.';

            exit;
        }

        // let us download the session-stored config without using cookies
        ini_set('session.use_only_cookies', false);

        $app['session']['installer'] = true;

        header(self::CONTENT_TYPE);

        echo $app['view']->responseBody;
    }

    public function getRouter() {
        return new Router_Install($this->app['url']->get());
    }

    public function exceptionHandler(Exception $e) {
        // too lazy to bother with this now
        var_dump($e);
    }
}

/* vim: set ts=4 sw=4 sts=4 et: */
