<?php

class View_Install_Restart extends View {
	protected function get($app) {
		unset($_SESSION['install_config']);
		diverge('/');
	}
}
