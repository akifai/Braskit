<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit;

use Pimple;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class App extends Pimple implements HttpKernelInterface {
    public function __construct() {
        parent::__construct();

        $app = $this;

        // load default services
        require __DIR__.'/../config/services.php';
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true) {
        try {
            $response = $this['controller']->run();
        } catch (\Exception $e) {
            $response = $this['controller']->exceptionHandler($e);
        }

        return $response;
    }

    public function run() {
        $response = $this->handle($this['request']);
        $response->send();
    }

    /**
     * Attempts to load config.php.
     *
     * @return boolean True if config.php was loaded.
     */
    public function loadConfig() {
        $app = $this;

        return (@include $this['path.root'].'/config.php') !== false;
    }
}

/* vim: set ts=4 sw=4 sts=4 et: */
