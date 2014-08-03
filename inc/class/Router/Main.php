<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit\Router;

use Board; // todo
use Braskit\Router;

/**
 * Routes for ajax.php & board.php.
 */
class Main extends Router {
    protected $prefix = 'Braskit\\View\\';

    public function setRoutes() {
        // Regex for boards
        $board_re = '('.Board::BOARD_RE.')';

        // Regex for "safe numbers" - 1-99999999
        $num_re = '([1-9]\d{0,8})';

        $this->routes = array(
            // User actions
            "/$board_re/post" => 'Post',
            "/$board_re/delete" => 'Delete',
            "/$board_re/report" => 'Report',

            // Mod view
            "/$board_re/(?:$num_re(?:\\.html)?|index\\.html)?" => 'Page',
            "/$board_re/res/$num_re(?:\\.html)?" => 'Thread',

            // Mod board actions
            "/$board_re/ban" => 'Ban',
            "/$board_re/config" => 'Config',
            "/$board_re/edit" => 'BoardEdit',
            "/$board_re/rebuild" => 'Rebuild',

            // Mod global actions
            '/bans' => 'Bans',
            '/config' => 'Config',
            '/login' => 'Login',
            '/logout' => 'Logout',
            '/manage' => 'Manage',
            '/reports' => 'Reports',

            '/create_board' => 'BoardCreate',
            '/users(?:/(\w+))?' => 'Users',
        );
    }
}

/* vim: set ts=4 sw=4 sts=4 et: */
