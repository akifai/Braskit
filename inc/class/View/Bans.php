<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

class View_Bans extends View {
    protected function get($app) {
        $user = do_login($app);

        // TODO: Pagination
        $bans = $app['db']->allBans();

        $ip = $app['param']->get('ip');

        return $this->render('bans.html', array(
            'admin' => true,
            'bans' => $bans,
            'ip' => $ip,
        ));
    }

    protected function post($app) {
        $user = do_login($app);

        $app['csrf']->check();

        $param = $app['param'];

        // adding a ban
        $expire = $param->get('expire');
        $reason = $param->get('reason');
        $ip = $param->get('ip');

        if ($ip) {
            $ban = new BanCreate($ip);
            $ban->setReason($reason);
            $ban->setExpire($expire);

            $ban->add();
        }

        // lifting bans
        $lifts = $param->get('lift', 'string array');

        if ($lifts && !is_array($lifts)) {
            $lifts = array($lifts);
        }

        foreach ($lifts as $id) {
            Ban::delete($id);
        }

        diverge('/bans');
    }
}

/* vim: set ts=4 sw=4 sts=4 et: */
