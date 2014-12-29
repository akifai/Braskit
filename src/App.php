<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit;

use Pimple\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\EventListener\RouterListener;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Symfony\Component\Routing\RequestContext;

class App extends Container implements HttpKernelInterface {
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
        $dispatcher = $this['event'];

        // our request matcher
        $matcher = new RequestMatcher($this);

        $context = new RequestContext();
        $context = $context->fromRequest($request);

        // should this be here?
        $dispatcher->addSubscriber(new RouterListener($matcher, $context));

        $resolver = new ControllerResolver();
        $kernel = new HttpKernel($dispatcher, $resolver);

        $response = $kernel->handle($request);

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
