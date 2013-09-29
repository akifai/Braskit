<?php
defined('TINYIB') or exit;

$board_re = '([A-Za-z0-9]+)';
$num_re = '([1-9]\d{0,9})';

$routes = array(
	'/' => 'home',

	// User actions
	"/$board_re/post" => 'post',
	"/$board_re/delete" => 'delete',
	"/$board_re/report" => 'report',

	// Mod view
	"/$board_re/(?:$num_re(?:\\.html)?|index\\.html)?" => 'viewpage',
	"/$board_re/res/$num_re(?:\\.html)?" => 'viewthread',

	// Mod board actions
	"/$board_re/ban" => 'ban',
	"/$board_re/config" => 'config',
	"/$board_re/edit" => 'edit_board',
	"/$board_re/rebuild" => 'rebuild',

	// Mod global actions
	'/login' => 'login',
	'/logout' => 'logout',
	'/manage' => 'manage',

	'/bans' => 'bans',
	'/add_ban' => 'addban',
	"/lift_ban(?:/$num_re)?" => 'liftban',
	"/edit_ban/$num_re" => 'editban',

	'/config' => 'config',

	'/create_board' => 'create_board',
	'/users(?:/(\w+))?' => 'users',
);
