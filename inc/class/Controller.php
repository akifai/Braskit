<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

abstract class Controller {
    protected $app;

    public function __construct(App $app) {
        $this->app = $app;

        $self = $this;

        $app['router'] = function () use ($self) {
            return $self->getRouter();
        };
    }

    abstract public function run();

    /**
     * Exception handler.
     *
     * @param Exception $e
     * @return void
     */
    abstract public function exceptionHandler(Exception $e);

    /**
     * Get an instance of Router.
     *
     * @return Router
     */
    abstract protected function getRouter();

    /**
     * Do stuff that messes with PHP's global state.
     */
    protected function globalSetup() {
        // bad things could happen without this
        ignore_user_abort(true);

        // TODO
        define('TINYIB', null);
        define('TINYIB_ROOT', $this->app['path.root']);

        // TODO
        require($this->app['path.root'].'/inc/functions.php');

        date_default_timezone_set($this->app['timezone']);

        if (get_magic_quotes_gpc()) {
            // some functions will alter their output depending on what this is
            // set to. cleaning up GET/POST/COOKIE is done in the Request class.
            set_magic_quotes_runtime(false);
        }
    }
}

/* vim: set ts=4 sw=4 sts=4 et: */
