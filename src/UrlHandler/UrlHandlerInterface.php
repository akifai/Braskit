<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit\UrlHandler;

use Symfony\Component\HttpFoundation\Request;

/**
 * Interface for URL stuff.
 *
 * A "path" refers to the internal path used in routing, such as /reports.
 */
interface UrlHandlerInterface {
    /**
     * Creates a URL, optionally with parameters.
     *
     * @param string $path The path to create a URL for.
     * @param array $parameters
     * @param Request|null $request Optional request.
     *
     * @return string The generated URL.
     */
    public function createURL($path, array $params = [], Request $request = null);

    /**
     * Retrieves the internal path for a request, which can be matched by the
     * router.
     *
     * @param Request|string A Request object or URL in string form.
     *
     * @return string The current URL.
     */
    public function getPath($request);
}
