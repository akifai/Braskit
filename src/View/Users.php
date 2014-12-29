<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit\View;

use Braskit\Error;
use Braskit\View;

class Users extends View {
    public function get($app, $username = false) {
        $user = $app['auth']->authenticate();

        $vars = array(
            'admin' => true,
            'editing' => false,
            'user' => $user,
        );

        if ($username === false) {
            $vars['users'] = $app['user']->getAll();
        } else {
            $vars['editing'] = true;
            $vars['target'] = $app['user']->get($username);
        }

        return $this->render('users.html', $vars);
    }

    public function post($app, $username = false) {
        $user = $app['auth']->authenticate();

        $app['csrf']->check();

        $param = $app['param'];

        // Form parameters
        $new_username = trim($param->get('username'));
        $email = trim($param->get('email'));
        $password = trim($param->get('password'));
        $password2 = trim($param->get('password2'));
        $level = abs(trim($param->get('level')));

        if ($username !== false) {
            // Edit user
            $target = $app['user']->edit($username, $user);
        } else {
            // Add user
            $target = $app['user']->create($user);

            // Check password
            if ($password === '' || $password !== $password2) {
                throw new Error('Invalid password');
            }
        }

        $target->setUsername($new_username);
        $target->setPassword($password);
        $target->setEmail($email);
        $target->setLevel($level);

        $target->commit();

        return $this->diverge('/users');
    }
}
