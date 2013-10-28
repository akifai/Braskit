<?php
defined('TINYIB') or exit;

function download_get($url) {
	if (!isset($_SESSION['install_config'])) {
		$flags = PARAM_STRING | PARAM_SERVER | PARAM_STRICT;
		$https = (bool)param('HTTPS', $flags);

		// No config stored
		header('HTTP/1.1 404 Not Found');

		// this needs to show a message usable for command line users
		// as well as for browser users
		printf("[<a href=\"%s\">Click</a>]<br>\n\n", get_script_name());
		printf("Go to http%s://%s%s to start the installation.\n",
			$https ? 's' : '',
			$_SERVER['SERVER_NAME'],
			get_script_name());

		return;
	}

	// offer the config as a download
	header('Content-Disposition: attachment; filename=config.php');
	echo $_SESSION['install_config'];
}
