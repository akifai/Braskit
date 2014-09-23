<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit;

use Braskit\Ban;
use Braskit\Error\BanError;

class BanService {
    /**
     * @var Database
     */
    protected $db;

    /**
     * Constructor.
     *
     * @param Database $db
     */
    public function __construct(Database $db) {
        $this->db = $db;
    }

    /**
     * Retrieves every ban.
     */
    public function getAll() {
        return $this->db->allBans();
    }

    /**
     * Retrieves a ban by its ID.
     */
    public function getBan($id) {
        return $this->banByID($id);
    }

    /**
     * Checks if an IP is banned.
     *
     * @throws BanException if the IP is banned
     */
    public function check($ip, $time = false) {
        if ($time === false) {
            $time = time();
        }

        $bans = $this->db->activeBansByIP($ip, $time);

        if (!$bans) {
            // not banned
            return;
        }

        $e = new BanError("Host is banned ($ip)");
        $e->setBans($bans);
        $e->ip = $ip;

        throw $e;
    }

    public function add(Ban $ban) {
        try {
            // add the ban to the database
            $this->id = $this->db->insertBan($ban);
        } catch (\PDOException $e) {
            // that failed for some reason - get the error code
            $errcode = $e->getCode();

            switch ($errcode) {
            case PgError::INVALID_TEXT_REPRESENTATION:
                // this happens when the IP is not valid!
                throw new Error('Invalid IP address.');
            case PgError::UNIQUE_VIOLATION:
                // do nothing
                break;
            default:
                // unexpected error
                throw $e;
            }
        }
    }

    /**
     * Deletes a ban by its ID.
     *
     * @return boolean Whether or not a ban was removed.
     */
    public function delete($id) {
        return $this->db->deleteBanByID($id);
    }
}
