<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

class App extends Pimple {
    public function __construct() {
        parent::__construct();

        $app = $this;

        // load default services
        require(dirname(__FILE__).'/../services.php');
    }

    /**
     * @deprecated
     */
    public function __toString() {
        return $this['path']->get();
    }

    public function run() {
        try {
            $this['controller']->run();
        } catch (Exception $e) {
            $this['controller']->exceptionHandler($e);
        }
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
