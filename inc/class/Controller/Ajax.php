<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit\Controller;

use Braskit\Controller;
use Router_Main; // todo

/**
 * Controller for ajax.php
 */
class Ajax extends Controller {
    const CONTENT_TYPE = 'Content-Type: application/json; charset=UTF-8';

    public function run() {
        // FIXME - this is needed because of the diverge() function
        global $ajax;

        while (ob_get_level()) {
            ob_end_clean();
        }

        // never let PHP print errors - this fucks up the JSON
        ini_set('display_errors', 0);

        $this->globalSetup();

        ob_start(array($this, 'obHandler'));

        define('TINYIB_BASE_TEMPLATE', 'base/ajax.html'); // TODO

        $hasConfig = $this->app->loadConfig();

        if (!$hasConfig) {
            // redirect to installer
            redirect('install.php');

            return;
        }

        $ajax = array(
            'error' => false,
        );

        header(self::CONTENT_TYPE);

        echo $this->app['view']->responseBody;

        ob_end_flush();
    }

    public function getRouter() {
        return new Router_Main($this->app['url']->get());
    }

    public function obHandler($buffer) {
        global $ajax;

        $ajax['page'] = $buffer;

        return json_encode($ajax);
    }

    public function exceptionHandler(\Exception $e) {
        ob_end_clean();

        header('HTTP/1.1 403 Forbidden');

        echo json_encode(array(
            'error' => true,
            'errorMsg' => $e->getMessage(),
        ));
    }
}

/* vim: set ts=4 sw=4 sts=4 et: */
