<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

class View_Install_Config extends View {
	protected function get($app) {
		if (!isset($app['session']['install_config'])) {
			// we haven't even begun
			diverge('/');
			exit;
		}

		$session_name = $app['session']->getName();
		$session_id = $app['session']->getID();

		$protocol = $app['request']->getProtocol();
		$hostname = $app['request']->getHostName();
		$baseurl = $protocol.'://'.$hostname;

		$config_path = expand_path('get_config', array(
			$session_name => $session_id
		));

		return $this->render('install_config.html', array(
			'config' => $app['session']['install_config'],
			'config_path' => $config_path,
			'config_url' => $baseurl.$config_path,
			'session_name' => $session_name,
			'session_id' => $session_id,
		));
	}
}
