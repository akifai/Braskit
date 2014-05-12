<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

class View_Delete extends View {
	protected function get($app) {
		return $this->csrfScreen();
	}

	protected function post($app, $boardname) {
		$param = $app['param'];

		$task = $param->get('task');
		$is_admin = $param->get('admin');

		$ids = $param->get('id', 'string array');

		// passwords from POST and cookie, respectively
		$password = $param->get('password', 'post');
		$cookie_pw = $param->get('password', 'cookie');

		$board = new Board($boardname);

		$user = null;

		// the deletion form is also used for reporting
		if (trim(strtolower($task)) === 'report') {
			// redirect to the report form
			redirect($board->path('report', array('id' => $ids)));
			return;
		}

		if ($is_admin) {
			$app['csrf']->check();
			$user = do_login();

			$password = null;
		} elseif ($password === '' || $password !== $cookie_pw) {
			// the passwords were either blank or not equal
			throw new Exception('Incorrect password for deletion.');
		}

		// Most delete actions will take place from the user delete form, which
		// sends post IDs as id[].
		if (!is_array($ids)) {
			$ids = array($ids);
		} else {
			$ids = array_unique($ids);
			sort($ids);
		}

		// Where to redirect after deleting
		$nexttask = $user ? $param->get('goto') : false;

		// Nothing to do
		if (!$ids && $nexttask) {
			diverge($nexttask);
			return;
		} elseif (!$ids) {
			redirect($board->path('index.html'));
			return;
		}

		$deleted_posts = array();
		$rebuild_queue = array();
		$error = false;

		foreach ($ids as $id) {
			if (isset($deleted_posts[$id])) {
				// Skip if post was deleted
				continue;
			}

			try {
				// try deleting the post
				$posts = $board->delete($id, $password);

				foreach ($posts as $post) {
					// mark post id as deleted
					$deleted_posts[$post->id] = true;

					if ($post->parent) {
						// Collect threads to be rebuilt
						$rebuild_queue[$post->parent] = true;
					}
				}
			} catch (PDOException $e) {
				$err = $e->getCode();

				if ($err === PgError::INVALID_PASSWORD) {
					// invalid password
					$error = true;
				} else {
					throw $e;
				}
			}
		}

		// Rebuild threads
		foreach ($rebuild_queue as $id) {
			$board->rebuildThread($id);
		}

		// Rebuild indexes
		$board->rebuildIndexes();

		// Show an error if any posts had the incorrect password.
		if ($error) {
			throw new Exception('Incorrect password for deletion.');
		}

		if ($nexttask) {
			diverge($nexttask);
			return;
		}

		redirect($board->path('index.html', (bool)$user));
	}
}
