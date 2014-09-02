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
    protected function get($app, $username = false) {
        $user = do_login($app);

        $vars = array(
            'admin' => true,
            'editing' => false,
            'user' => $user,
        );

        if ($username === false) {
            $vars['users'] = $app['db']->getUserList();
        } else {
            $vars['editing'] = true;
            $vars['target'] = $user->edit($username);
        }

        return $this->render('users.html', $vars);
    }

    protected function post($app, $username = false) {
        $user = do_login($app);

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
            $target = $user->edit($username);

            $target->setUsername($new_username);

            // Set new password if it's not blank in the form
            if ($password !== '')
                $target->setPassword($password);

            $target->setEmail($email);
            $target->setLevel($level);
        } else {
            // Add user
            $target = $user->create($new_username);

            // Check password
            if ($password === '' || $password !== $password2)
                throw new Error('Invalid password');

            $target->setEmail($email);
            $target->setPassword($password);
            $target->setLevel($level);
        }

        $target->commit();

        diverge('/users');
    }
}

/* vim: set ts=4 sw=4 sts=4 et: */