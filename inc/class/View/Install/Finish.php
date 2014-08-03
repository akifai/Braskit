<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit\View\Install;

use Braskit\Database;
use Braskit\Database\Connection;
use Braskit\User\Admin;
use Braskit\View;

class Finish extends View {
    protected function get($app) {
        // we don't belong here yet
        if (!file_exists(TINYIB_ROOT.'/config.php')) {
            if (!isset($app['session']['installer_secret'])) {
                diverge('/');
            } else {
                diverge('/config');
            }

            return;
        }

        // load config
        require(TINYIB_ROOT.'/config.php');

        // this makes sure that the person who placed config.php in the
        // root dir is the same person finishing the install
        if ($app['session']['installer_secret'] !== $app['secret']) {
            throw new Error('Fuck off.');
        }

        // connect to database
        $app['dbh'] = new Connection(
            $app['db.name'],
            $app['db.host'],
            $app['db.username'],
            $app['db.password'],
            $app['counter']
        );

        $app['db'] = new Database($app['dbh'], $app['db.prefix']);

        $app['dbh']->beginTransaction();

        $app['db']->initDatabase();

        // create our user account
        $user = new Admin();

        $u = $user->create($app['session']['installer_user']);
        $u->setPassword($app['session']['installer_pass']);
        $u->setLevel(9999);
        $u->commit();

        // if something fails, nothing is committed to the database
        $app['dbh']->commit();

        // and we're done! clear our session and redirect
        $app['session']->clean();

        redirect('board.php?/login');
    }
}

/* vim: set ts=4 sw=4 sts=4 et: */
