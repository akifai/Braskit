<?php

class View_Bans extends View {
	protected function get($url) {
		global $app;

		$user = do_login($url);

		// TODO: Pagination
		$bans = $app['db']->allBans();

		$ip = $app['param']->get('ip');

		return $this->render('bans.html', array(
			'admin' => true,
			'bans' => $bans,
			'ip' => $ip,
		));
	}

	protected function post($url) {
		global $app;

		$user = do_login($url);
		do_csrf();

		$param = $app['param'];

		// adding a ban
		$expire = $param->get('expire');
		$reason = $param->get('reason');
		$ip = $param->get('ip');

		if ($ip) {
			$ban = new BanCreate($ip);
			$ban->setReason($reason);
			$ban->setExpire($expire);

			$ban->add();
		}

		// lifting bans
		$lifts = $param->get('lift', Param::S_DEFAULT | Param::T_ARRAY);

		if ($lifts && !is_array($lifts)) {
			$lifts = array($lifts);
		}

		foreach ($lifts as $id) {
			Ban::delete($id);
		}

		diverge('/bans');
	}
}
