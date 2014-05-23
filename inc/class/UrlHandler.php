<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit;

abstract class UrlHandler {
    protected $request;
    protected $entryPoints = array();

    protected $url = null;

    public function __construct(\Request $request, array $entrypoints) {
        $this->request = $request;

        $this->entryPoints = $entrypoints;
    }

    /**
     * Creates an internal URL, optionally with parameters.
     *
     * @param string $path The path to create a URL for.
     * @param mixed $parameters
     */
    abstract public function create($path, $params);

    /**
     * Retrieves the current URL.
     *
     * @return string The current URL.
     */
    public function get() {
        if ($this->url === null) {
            $this->url = $this->findUrl();
        }

        return $this->url;
    }

    /**
     * Finds the URL of the current request and returns it.
     *
     * @return string
     */
    abstract protected function findUrl();
}

/* vim: set ts=4 sw=4 sts=4 et: */
