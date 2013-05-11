<?php
defined('TINYIB') or exit;

function home_get() {
	global $config;
	redirect($config->home_url);
}
