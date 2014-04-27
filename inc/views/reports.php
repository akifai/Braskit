<?php

class View_Reports extends View {
	protected function get($url) {
		do_csrf($url);
	}

	protected function post($url) {
		global $app;

		do_csrf($url);
		$user = do_login();

		$flags = PARAM_DEFAULT | PARAM_ARRAY;
		$dismiss = param('dismiss', $flags);

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
