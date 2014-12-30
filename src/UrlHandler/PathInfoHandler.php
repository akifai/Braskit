<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit\UrlHandler;

use Symfony\Component\HttpFoundation\Request;

/**
 * URL handler that uses path info.
 *
 * Example: http://example.com/board.php/some-path?arg1=foo&arg2=bar
 */
class PathInfoHandler implements UrlHandlerInterface {
    protected $request;

    public function __construct(Request $request) {
        $this->request = $request;
    }

    /**
     * {@inheritdoc}
     */
    public function createURL($path, array $params = [], Request $request = null) {
        $args = http_build_query($params);

        if (strlen($args)) {
            $args = "?$args";
        }

        $request = $request ?: $this->request;

        return $request->getScriptName().$path.$args;
    }

    /**
     * {@inheritdoc}
     */
    public function getPath($request) {
        $path = $request->server->get('PATH_INFO');

        if (!$path) {
            return '/';
        }

        return $path;
    }
}
