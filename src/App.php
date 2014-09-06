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
     * @deprecated
     */
    public function __toString() {
        return $this['url']->get();
    }

    /**
     * {@inheritdoc}
     *
     * @todo This is a giant hack, it'll be redone once our views actually use
     *       the Response class from symfony.
     */
    public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true) {
        $defaultCode = http_response_code();
        $defaultHeaders = headers_list();

        header_remove();

        ob_start();

        try {
            $this['controller']->run();
        } catch (\Exception $e) {
            $this['controller']->exceptionHandler($e);
        }

        $headers = [];

        foreach (headers_list() as $header) {
            list($field, $value) = explode(':', $header);
            $headers[trim($field)][] = trim($value);
        }

        $code = http_response_code();
        http_response_code($defaultCode);

        header_remove();

        foreach ($defaultHeaders as $header) {
            header($header);
        }

        $response = new Response(ob_get_clean(), $code, $headers);

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
