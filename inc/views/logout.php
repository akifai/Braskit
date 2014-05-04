<?php

class View_Logout extends View {
	protected function get($app) {
		if (isset($app['session']['login'])) {
			unset($app['session']['login']);
		}

		diverge('/login');
	}
}
