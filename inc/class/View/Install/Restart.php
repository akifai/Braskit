<?php

class View_Install_Restart extends View {
	protected function get($app) {
		unset($app['session']['install_config']);

		diverge('/');
	}
}
