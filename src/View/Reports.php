<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit\View;

use Braskit\View;

class Reports extends View {
    public function get($app) {
        return $this->csrfScreen();
    }

    public function post($app) {
        $user = $app['auth']->authenticate();

        $app['csrf']->check();

        $dismiss = $app['param']->get('dismiss', 'string array');

        if (!is_array($dismiss)) {
            $dismiss = array($dismiss);
        }

        $dismiss = array_filter($dismiss, 'ctype_digit');

        if ($dismiss) {
            $app['db']->dismissReports($dismiss);
        }

        return $this->diverge('/reports');
    }
}
