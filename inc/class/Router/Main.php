<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

/**
 * Routes for ajax.php & board.php.
 */
class Router_Main extends Router {
    public function setRoutes() {
        // Regex for boards
        $board_re = '('.Board::BOARD_RE.')';

        // Regex for "safe numbers" - 1-99999999
        $num_re = '([1-9]\d{0,8})';

        $this->routes = array(
            // User actions
            "/$board_re/post" => 'View_Post',
            "/$board_re/delete" => 'View_Delete',
            "/$board_re/report" => 'View_Report',

            // Mod view
            "/$board_re/(?:$num_re(?:\\.html)?|index\\.html)?" => 'View_Page',
            "/$board_re/res/$num_re(?:\\.html)?" => 'View_Thread',

            // Mod board actions
            "/$board_re/ban" => 'View_Ban',
            "/$board_re/config" => 'View_Config',
            "/$board_re/edit" => 'View_BoardEdit',
            "/$board_re/rebuild" => 'View_Rebuild',

            // Mod global actions
            '/bans' => 'View_Bans',
            '/config' => 'View_Config',
            '/login' => 'View_Login',
            '/logout' => 'View_Logout',
            '/manage' => 'View_Manage',
            '/reports' => 'View_Reports',

            '/create_board' => 'View_BoardCreate',
            '/users(?:/(\w+))?' => 'View_Users',
        );
    }
}

/* vim: set ts=4 sw=4 sts=4 et: */
