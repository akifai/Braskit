<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit;

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

    /**
     * Shortcut method for quickly creating a new ban object.
     */
    public static function create($ip, $time = null) {
        $ban = new static();

        $ban->timestamp = is_int($time) ? $time : time();
        $ban->ip = $ip;

        if ($ban->ip === '') {
            throw new Error('No IP entered.');
        }

        return $ban;
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
}

/* vim: set ts=4 sw=4 sts=4 et: */
