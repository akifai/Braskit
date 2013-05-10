<?php
defined('TINYIB') or exit;

function restart_get() {
	unset($_SESSION['install_config']);
	diverge('/');
}
