<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

class View_Logout extends View {
	protected function get($app) {
		if (isset($app['session']['login'])) {
			unset($app['session']['login']);
		}

		diverge('/login');
	}
}
