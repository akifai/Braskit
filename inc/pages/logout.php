<?php
defined('TINYIB') or exit;

function logout_get($url) {
	if (isset($_SESSION['login']))
		unset($_SESSION['login']);

	diverge('/login');
}
