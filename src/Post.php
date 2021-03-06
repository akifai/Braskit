<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit;

class Post extends FileMetaData {
    public $globalid = 0;
    public $id = 0;
    public $parent = 0;
    public $board = '';
    public $timestamp = 0;
    public $lastbump = 0;
    public $ip = '127.0.0.2';
    public $name = '';
    public $tripcode = '';
    public $email = '';
    public $subject = '';
    public $comment = '';
    public $password = '';
    public $date = '';
    public $unixtime = 0;
    public $banned = false;
    public $reports = null;
}

/* vim: set ts=4 sw=4 sts=4 et: */
