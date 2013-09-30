<?php
defined('TINYIB') or exit;

class Post {
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
	public $file = '';
	public $md5 = '';
	public $origname = '';
	public $filesize = 0;
	public $prettysize = '';
	public $width = 0;
	public $height = 0;
	public $thumb = '';
	public $t_width = 0;
	public $t_height = 0;
	public $date = '';
	public $unixtime = 0;
	public $banned = false;
	public $reports = null;
}
