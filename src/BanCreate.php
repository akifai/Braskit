<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit;

class BanCreate extends Ban {
    public function __construct($ip) {
        $this->timestamp = time();

        // remove whitespace
        $ip = trim($ip);

        if ($ip === '') {
            throw new Error('No IP entered.');
        }

        $this->ip = $ip;
    }

    /// @todo Unused
    public function setBoard(Board $board) {
        $this->board = $board;
    }

    /// @todo Unused
    public function setPost(Post $post) {
        $this->post = $post;

        if ($post->board instanceof Board) {
            // we already have a board object
            $this->setBoard($post->board);
        } elseif (strlen($post->board)) {
            try {
                // create a new board object
                $board = new Board($post->board, false, false);

                $this->setBoard($board);
            } catch (\PDOException $e) {
                // database error
                throw $e;
            } catch (\LogicException $e) {
                // programmatic error
                throw $e;
            }
            // ignore any other kinds of error
        }
    }

    public function setReason($reason) {
        $this->reason = trim($reason);
    }

    public function setExpire($expire) {
        if ($expire && ctype_digit($expire)) {
            // expiry time + request time = when the ban expires
            $expire += time();

            $this->expire = $expire;
        }
    }

    public function add($update = false) {
        global $app;

        try {
            // add the ban to the database
            $this->id = $app['db']->insertBan($this);
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

        if ($update) {
            $new = Ban::get($id);

            // is there a better way?
            $this->id = $new->id;
            $this->ip = $new->ip;
            $this->host = $new->host;
            $this->cidr = $new->cidr;
            $this->ipv6 = $new->ipv6;
            $this->range = $new->range;
            $this->timestamp = $new->timestamp;
            $this->expire = $new->expire;
            $this->reason = $new->reason;
        }
    }
}

/* vim: set ts=4 sw=4 sts=4 et: */
