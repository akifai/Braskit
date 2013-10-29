<?php
defined('TINYIB') or exit;

function reports_get($url) {
	do_csrf($url);
}

function reports_post($url) {
	do_csrf($url);
	$user = do_login();

	$flags = PARAM_DEFAULT | PARAM_ARRAY;
	$dismiss = param('dismiss', $flags);

	if (!is_array($dismiss)) {
		$dismiss = array($dismiss);
	}

	$dismiss = array_filter($dismiss, 'ctype_digit');

	if ($dismiss) {
		dismissReports($dismiss);
	}

	diverge('/reports');
}
