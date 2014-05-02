<?php

class View_Install_Start extends View {
	protected function get($app) {
		if (isset($_SESSION['install_config'])) {
			diverge('/config');
			return;
		}

		return $this->render('install.html');
	}

	protected function post($app) {
		require('inc/config_template.php');

		$param = $app['param'];

		// set up config variables
		$vars = array();

		foreach (array(
			'db_name',
			'db_username',
			'db_password',
			'db_host',
			'db_prefix',
			'username',
			'password'
		) as $name) {
			$vars[$name] = $param->get($name);
		}

		// generate a secret key
		$_SESSION['installer_secret'] = $vars['secret'] = random_string(60);

		// unique identifier
		$vars['unique'] = 'pib'.mt_rand(10, 99);

		// note: we use sessions to store the config because we don't want
		// other people to see the finished config!
		$_SESSION['install_config'] = @create_config($vars);

		// we need these for the last step
		$_SESSION['installer_user'] = $vars['username'];
		$_SESSION['installer_pass'] = $vars['password'];

		diverge('/config');
	}
}
