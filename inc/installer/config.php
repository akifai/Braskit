<?php

class View_Install_Config extends View {
	protected function get($app) {
		if (!isset($app['session']['install_config'])) {
			// we haven't even begun
			diverge('/');
			exit;
		}

		$session_name = $app['session']->getName();
		$session_id = $app['session']->getID();

		$config_path = expand_path('get_config', array(
			$session_name => $session_id
		));

		return $this->render('install_config.html', array(
			'config' => $app['session']['install_config'],
			'config_path' => $config_path,
			'config_url' => get_url($config_path),
			'session_name' => $session_name,
			'session_id' => $session_id,
		));
	}
}
