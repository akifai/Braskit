<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit;

use Braskit\UrlHandler\UrlHandlerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;

/**
 * Request matcher for our horrible routing system and horrible view classes.
 * This is basically the glue between Braskit's routing system and Symfony's.
 *
 * Note that 'controller' and 'view' are synonymous in Braskit.
 */
class RequestMatcher implements RequestMatcherInterface {
    /**
     * @var App
     */
    protected $app;

    /**
     * @var string
     */
    protected $routerClass;

    /**
     * @var UrlHandlerInterface
     */
    protected $handler;

    /**
     * Constructor.
     *
     * Views must be constructed with $app as their parameter, so we have no
     * choice but to include it here.
     *
     * @param Application $app
     */
    public function __construct(App $app) {
        $this->app = $app;

        $this->routerClass = $app['router_class'];
        $this->handler = $app['url'];
    }

    /**
     * {@inheritdoc}
     */
    public function matchRequest(Request $request) {
        // retrieve the internal path for route matching
        $path = $this->handler->getPath($request);

        $router = new $this->routerClass($path, function () {
            // executed on 404
            throw new ResourceNotFoundException();
        });

        if (!class_exists($router->view)) {
            // view class doesn't exist
            throw new \LogicException("No such view '$router->view'");
        }

        $http_method = $request->getRealMethod();

        if (!method_exists($router->view, $http_method)) {
            // The view class doesn't contain the appropriate get/post/whatever
            // method.
            $allowed_methods = $this->getAllowedMethods($router->view);

            throw new MethodNotAllowedException($allowed_methods);
        }

        // we need to provide the callback that invokes the view
        return ['_controller' => function () use ($router, $http_method) {
            // create a view object
            $view = new $router->view($this->app);
            
            // combine $app and the rest of the matches into one array
            $args = array_merge([$this->app], $router->matches);

            // return the response
            return call_user_func_array([$view, $http_method], $args);
        }];
    }

    /**
     * Retrieves the allowed HTTP methods for a View class.
     *
     * @return array
     */
    protected function getAllowedMethods($class) {
        // http/1.1
        $methods = ['get', 'head', 'post', 'put', 'delete', 'trace', 'connect'];

        for ($i = count($methods); $i--;) {
            if (!method_exists($class, $methods[$i])) {
                unset($methods[$i]);
            }
        }

        return $methods;
    }
}
