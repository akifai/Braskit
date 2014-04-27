<?php

class View_Config extends View {
	protected function get($url, $boardname = false) {
		global $app;

		$user = do_login($url);

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

	protected function post($url, $boardname = false) {
		global $app;

		$user = do_login($url);

		do_csrf();

		if ($boardname !== false) {
			$board = new Board($boardname);
			$instance = $board->config;
		} else {
			$instance = $app['config'];
		}

		$flags = PARAM_DEFAULT | PARAM_ARRAY;
		$values = param('config', $flags);
		$reset = param('reset', $flags);

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

		diverge($url);
	}
}
