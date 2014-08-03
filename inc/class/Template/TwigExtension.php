<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit\Template;

use Style;

use Twig_Extension;
use Twig_SimpleFilter as SimpleFilter;
use Twig_SimpleFunction as SimpleFunction;

class TwigExtension extends Twig_Extension {
    public function getFunctions() {
        $functions = array(
            new SimpleFunction('js', 'get_js'),
            new SimpleFunction('path', 'expand_path'),
            new SimpleFunction('apipath', 'expand_api_path'),
        );

        return $functions;
    }

    public function getFilters() {
        $filters = array(
            new SimpleFilter('json_decode', 'json_decode'),
        );

        return $filters;
    }

    public function getGlobals() {
        global $app;

        $globals = array(
            'app' => $app,
            '_base' => 'base/main.html',
            'self' => $app['request']->getScriptName(),
            'style' => Style::getObject(),
        );

        if (defined('TINYIB_BASE_TEMPLATE')) {
            $globals['_base'] = TINYIB_BASE_TEMPLATE;
        }

        return $globals;
    }

    public function getName() {
        return 'braskit';
    }
}

/* vim: set ts=4 sw=4 sts=4 et: */
