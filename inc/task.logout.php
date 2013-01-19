<?php
defined('TINYIB_BOARD') or exit;

function logout_get() {
	session_destroy();
	redirect(get_script_name().'?task=login');
}
