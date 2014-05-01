<?php

class View_Logout extends View {
	protected function get($app) {
		if (isset($_SESSION['login']))
			unset($_SESSION['login']);

		diverge('/login');
	}
}
