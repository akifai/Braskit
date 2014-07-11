<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

class View_Install_Start extends View {
    protected function get($app) {
        if (isset($app['session']['install_config'])) {
            diverge('/config');
            return;
        }

        return $this->render('install.html');
    }

    protected function post($app) {
        require('inc/config_template.php');

        $param = $app['param'];

        // set up config variables
        $vars = array();

        foreach (array(
            'db_name',
            'db_username',
            'db_password',
            'db_host',
            'db_prefix',
            'username',
            'password'
        ) as $name) {
            $vars[$name] = $param->get($name);
        }

        // generate a secret key
        $vars['secret'] = random_string(60);
        $app['session']['installer_secret'] = $vars['secret'];

        // unique identifier
        $vars['unique'] = 'bs'.mt_rand(10, 99);

        // note: we use sessions to store the config because we don't want
        // other people to see the finished config!
        $app['session']['install_config'] = @create_config($vars);

        // we need these for the last step
        $app['session']['installer_user'] = $vars['username'];
        $app['session']['installer_pass'] = $vars['password'];

        diverge('/config');
    }
}

/* vim: set ts=4 sw=4 sts=4 et: */
