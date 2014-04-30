<?php

class View_Install_Download extends View {
	protected function get($url) {
		global $app;

		if (!isset($_SESSION['install_config'])) {
			$param = $app['param']->flags(
				Param::T_STRING |
				Param::M_SERVER |
				Param::S_STRICT
			);

			$https = (bool)param('HTTPS');

			// No config stored
			header('HTTP/1.1 404 Not Found');

			// this needs to show a message usable for command line users
			// as well as for browser users
			printf("[<a href=\"%s\">Click</a>]<br>\n\n", get_script_name());
			printf("Go to http%s://%s%s to start the installation.\n",
				$https ? 's' : '',
				$app['request']->server['SERVER_NAME'],
				get_script_name());

			return;
		}

		// offer the config as a download
		header('Content-Disposition: attachment; filename=config.php');
		echo $_SESSION['install_config'];
	}
}
