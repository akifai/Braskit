<?php
defined('TINYIB') or exit;

function logout_get() {
	session_destroy();
	redirect(get_script_name().'?task=login');
}
