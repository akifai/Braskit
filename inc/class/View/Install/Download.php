<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

class View_Install_Download extends View {
	protected function get($app) {
		if (!isset($app['session']['install_config'])) {
			// No config stored
			header('HTTP/1.1 404 Not Found');

			// this needs to show a message usable for command line users
			// as well as for browser users
			printf("[<a href=\"%s\">Click</a>]<br>\n\n",
				$app['request']->getScriptName()
			);

			printf("Go to %s://%s%s to start the installation.\n",
				$app['request']->getProtocol(),
				$app['request']->getHostName(),
				$app['request']->getScriptName()
			);

			return;
		}

		// offer the config as a download
		header('Content-Disposition: attachment; filename=config.php');
		echo $app['session']['install_config'];
	}
}
