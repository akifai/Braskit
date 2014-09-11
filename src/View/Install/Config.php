<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit\View\Install;

use Braskit\View;

class Config extends View {
    protected function get($app) {
        if (!$app['session']->has('install_config')) {
            // we haven't even begun
            diverge('/');
            exit;
        }

        $session_name = $app['session']->getName();
        $session_id = $app['session']->getId();

        $protocol = $app['request']->getScheme();
        $hostname = $app['request']->getHost();
        $baseurl = $protocol.'://'.$hostname;

        $config_path = expand_path('get_config', array(
            $session_name => $session_id
        ));

        return $this->render('install_config.html', array(
            'config' => $app['session']->get('install_config'),
            'config_path' => $config_path,
            'config_url' => $baseurl.$config_path,
            'session_name' => $session_name,
            'session_id' => $session_id,
        ));
    }
}

/* vim: set ts=4 sw=4 sts=4 et: */
