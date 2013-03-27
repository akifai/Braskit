<?php
defined('TINYIB') or exit;

function delete_any() {
	$loggedin = check_login();

	$boardname = param('board');
	$password = param('password', PARAM_STRING | PARAM_COOKIE);
	$posts = param('delete', PARAM_DEFAULT | PARAM_ARRAY);

	// Where to redirect after deleting
	$nexttask = $loggedin
		? param('nexttask')
		: false;

	$board = new Board($boardname);

	// Nothing to do
	if (!$posts && $nexttask) {
		redirect(get_script_name().'?task='.$nexttask);
		return;
	} elseif (!$posts) {
		redirect(expand_path('index.html'));
		return;
	}

	// Most delete actions will take place from the user delete form, which
	// sends post IDs as delete[].
	if (!is_array($posts)) {
		$posts = array($posts);
	} else {
		$posts = array_unique($posts);
		sort($posts);
	}

	// check for blank password, which is always invalid
	if (!$loggedin && $password === '')
		throw new Exception('Incorrect password for deletion.');

	$rebuild_queue = array();
	$deleted_threads = array();

	$error = false;

	foreach ($posts as $id) {
		$post = $board->getPost($id);

		// Skip non-existent posts
		if ($post === false)
			continue;

		// Skip if parent is deleted
		if (array_key_exists($post['parent'], $deleted_threads))
			continue;

		// Check password
		if (!$loggedin && $post['password'] !== $password) {
			$error = true;
			continue;
		}

		$board->delete($id);

		// Collect parents so we can rebuild or delete them
		if ($post['parent'])
			$rebuild_queue[$post['parent']] = true;
		else
			$deleted_threads[$id] = true;
	}

	// Rebuild threads
	foreach ($rebuild_queue as $id)
		$board->rebuildThread($id);

	// Rebuild indexes
	$board->rebuildIndexes();

	// Show an error if any posts had the incorrect password.
	if ($error)
		throw new Exception('Incorrect password for deletion.');

	if ($nexttask)
		redirect(get_script_name().'?task='.$nexttask);
	else
		redirect($board->path('index.html'));
}

function delete_get() { delete_any(); }
function delete_post() { delete_any(); }
