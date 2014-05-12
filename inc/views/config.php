<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

class View_Config extends View {
	protected function get($app, $boardname = false) {
		$user = do_login($app);

		$template_vars = array('admin' => true);

		if ($boardname !== false) {
			$board = new Board($boardname);

			$template_vars['board'] = $board;
			$template_vars['config_instance'] = $board->config;
		} else {
			$template_vars['config_instance'] = $app['config'];
		}

		return $this->render('config.html', $template_vars);
	}

	protected function post($app, $boardname = false) {
		$user = do_login($app);

		$app['csrf']->check();

		if ($boardname !== false) {
			$board = new Board($boardname);
			$instance = $board->config;
		} else {
			$instance = $app['config'];
		}

		$param = $app['param']->flags('string array');
		$values = $param->get('config');
		$reset = $param->get('reset');

		if (!is_array($values))
			$values = array();

		if (!is_array($reset))
			$reset = array();

		// update values
		foreach ($instance as $item) {
			$key = $item['name'];

			if (isset($reset[$key]) && $reset[$key])
				continue;

			$instance->$key = isset($values[$key])
				? $values[$key]
				: '';
		}

		// reset values to their hardcoded defaults
		foreach ($reset as $key => $do_reset)
			if ($do_reset)
				unset($instance->$key);

		// save the config
		if ($values || $reset)
			$instance->save();

		diverge($app);
	}
}
