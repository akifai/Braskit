<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit\View;

use Braskit\Error;
use Braskit\User\Login as UserLogin;
use Braskit\View;

class Login extends View {
    protected function get($app) {
        $error = false;

        try {
            $user = do_login();
        } catch (Error $e) {
            $user = false;
            $error = $e->getMessage();
        }

        if ($user) {
            return $this->diverge('/manage');
        }

        // get login errors, if any
        $error = $app['session']->getFlashBag()->get('login-error');

        $goto = $app['param']->get('goto');

        return $this->render('login.html', array(
            'error' => $error,
            'goto' => $goto,
        ));
    }

    protected function post($app) {
        $session = $app['session'];

        $param = $app['param']->flags('post');

        $username = $param->get('login_user');
        $password = $param->get('login_pass');

        try {
            // validate user/pw
            $user = new UserLogin($username, $password);

            // this keeps us logged in
            $session->set('login', serialize($user));

            $loggedin = true;
        } catch (Error $e) {
            $loggedin = false;

            // store the error message we display after the redirect
            $session->getFlashBag()->set('login-error', $e->getMessage());
        }

        if ($loggedin) {
            $goto = urldecode($param->get('goto', 'default')) ?: '/manage';

            return $this->diverge($goto);
        }

        return $this->diverge('/login');
    }
}

/* vim: set ts=4 sw=4 sts=4 et: */
