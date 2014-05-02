<?php

class View_Install_Finish extends View {
	protected function get($app) {
		// we don't belong here yet
		if (!file_exists(TINYIB_ROOT.'/config.php')) {
			if (!isset($_SESSION['installer_secret'])) {
				diverge('/');
			} else {
				diverge('/config');
			}

			return;
		}

		$app = new App();

		// load config
		require(TINYIB_ROOT.'/config.php');

		// this makes sure that the person who placed config.php in the
		// root dir is the same person finishing the install
		if ($_SESSION['installer_secret'] !== $app['secret']) {
			throw new Exception('Fuck off.');
		}

		// connect to database
		$app['dbh'] = new DBConnection(
			$app['db.name'],
			$app['db.host'],
			$app['db.username'],
			$app['db.password']
		);

		$app['db'] = new Database($app['dbh'], $app['db.prefix']);

		$app['dbh']->beginTransaction();

		$app['db']->initDatabase();

		// create our user account
		$user = new UserAdmin();

		$u = $user->create($_SESSION['installer_user']);
		$u->setPassword($_SESSION['installer_pass']);
		$u->setLevel(9999);
		$u->commit();

		// if something fails, nothing is committed to the database
		$app['dbh']->commit();

		// and we're done! clear our session and redirect
		$_SESSION = array();
		redirect('board.php?/login');
	}
}
