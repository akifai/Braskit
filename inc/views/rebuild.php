<?php

class View_Rebuild extends View {
	protected function get($app, $boardname) {
		$user = do_login($app);

		$board = new Board($boardname);

		set_time_limit(0);

		$board->rebuildAll();

		redirect($board->path('index.html'));
	}
}
