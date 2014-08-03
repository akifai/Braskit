<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit;

use Braskit\Error\Ban as BanError;

class Ban {
    public $id = null;
    public $ip = null;
    public $host = null;
    public $cidr = null;
    public $ipv6 = false;
    public $range = false;
    public $timestamp = '';
    public $expire = null;
    public $reason = '';

    public $board;
    public $post;

    public static function getByID($id) {
        global $app;

        return $app['db']->banByID($id);
    }

    /**
     * Check if an IP is banned.
     *
     * @throws BanException if the IP is banned
     */
    public static function check($ip, $time = false) {
        global $app;

        if ($time === false)
            $time = time();

        $bans = $app['db']->activeBansByIP($ip, $time);

        if (!$bans) {
            // not banned
            return;
        }

        $e = new BanError("Host is banned ($ip)");
        $e->setBans($bans);
        $e->ip = $ip;

        throw $e;
    }

    /**
     * Deletes a ban by its ID.
     *
     * @returns boolean Whether or not a ban was removed.
     */
    public static function delete($id) {
        global $app;

        return $app['db']->deleteBanByID($id);
    }
}

/* vim: set ts=4 sw=4 sts=4 et: */
