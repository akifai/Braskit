<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

class View_Reports extends View {
	protected function get($app) {
		return $this->csrfScreen();
	}

	protected function post($app) {
		$user = do_login();

		$app['csrf']->check();

		$param = $app['param']->flags(Param::S_DEFAULT|Param::T_ARRAY);
		$dismiss = $param->get('dismiss');

		if (!is_array($dismiss)) {
			$dismiss = array($dismiss);
		}

		$dismiss = array_filter($dismiss, 'ctype_digit');

		if ($dismiss) {
			$app['db']->dismissReports($dismiss);
		}

		diverge('/reports');
	}
}
