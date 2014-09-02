<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit\UrlHandler;

use Braskit\UrlHandler;
use Symfony\Component\HttpFoundation\Request;

/**
 * URL subclass for query string-based routing and URLs. Will work in any setup,
 * but creates ugly URLs.
 */
class QueryString extends UrlHandler {
    protected $request;

    public function __construct(Request $request) {
        $this->request = $request;
    }

    public function create($task, $params) {
        $query = '?'.$task;

        if (is_array($params)) {
            $args = http_build_query($params);

            if ($args) {
                $query .= "&$args";
            }
        }

        return $query;
    }

    protected function findUrl() {
        $query = $this->request->server->get('QUERY_STRING');

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

/* vim: set ts=4 sw=4 sts=4 et: */
