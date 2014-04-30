<?php

class View_Reports extends View {
	protected function get($url) {
		do_csrf($url);
	}

	protected function post($url) {
		global $app;

		do_csrf($url);
		$user = do_login();

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
