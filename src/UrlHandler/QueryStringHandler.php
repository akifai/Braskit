<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit\UrlHandler;

use Symfony\Component\HttpFoundation\Request;

/**
 * URL handler that uses query strings.
 *
 * Example: http://example.com/board.php?/some-path&arg1=foo&arg2=bar
 */
class QueryStringHandler implements UrlHandlerInterface {
    protected $request;

    public function __construct(Request $request) {
        $this->request = $request;
    }

    /**
     * {@inheritdoc}
     */
    public function createURL($path, array $params = [], Request $request = null) {
        $query = '?'.$path;


        $args = http_build_query($params);

        if (strlen($args)) {
            $query .= "&$args";
        }

        $request = $request ?: $this->request;

        return $request->getScriptName().$query;
    }

    /**
     * {@inheritdoc}
     */
    public function getPath($request) {
        $query = $request->server->get('QUERY_STRING');

        if (!strlen($query) || $query[0] !== '/') {
            // the query string is either invalid or not defined
            return '/';
        }

        $pos = strpos($query, '&');

        if ($pos !== false) {
            // ignore other GET values
            $url = substr($query, 0, $pos);
        } else {
            // we can use the whole query string
            $url = $query;
        }

        return $url;
    }
}
