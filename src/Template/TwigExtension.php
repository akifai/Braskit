<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit\Template;

use Braskit\Style;

use Twig_Extension;
use Twig_SimpleFilter as SimpleFilter;
use Twig_SimpleFunction as SimpleFunction;

class TwigExtension extends Twig_Extension {
    public function getFunctions() {
        $functions = array(
            new SimpleFunction('js', 'get_js'),
            new SimpleFunction('path', 'expand_path'),
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
            'global_config' => $app['config']->getPool('global'),
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
