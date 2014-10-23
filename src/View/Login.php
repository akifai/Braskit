<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit\View;

use Braskit\Error;
use Braskit\View;

class Login extends View {
    protected function get($app) {
        $auth = $app['auth'];

        if ($auth->isLoggedIn()) {
            return $this->redirectAfterLogin();
        }

        // get login errors, if any
        $error = $app['session']->getFlashBag()->get('login-error');

        // get destination after login
        $goto = $app['param']->get('goto');

        return $this->render('login.html', [
            'error' => $error,
            'goto' => $goto,
        ]);
    }

    protected function post($app) {
        $auth = $app['auth'];
        $param = $app['param']->flags('post');
        $session = $app['session'];

        if ($auth->isLoggedIn()) {
            // nothing to do, redirect
            return $this->redirectAfterLogin();
        }

        $username = $param->get('login_user');
        $password = $param->get('login_pass');

        try {
            $auth->login($username, $password);

            // login successful, redirect
            return $this->redirectAfterLogin();
        } catch (Error $e) {
            // login failed, store the error
            $session->getFlashBag()->set('login-error', $e->getMessage());

            return $this->diverge('/login');
        }
    }

    private function redirectAfterLogin() {
        $goto = $this->app['param']->get('goto') ?: '/manage';

        return $this->diverge($goto);
    }
}
