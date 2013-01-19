<?php
defined('TINYIB_BOARD') or exit;

function delete_get() {
	$loggedin = check_login();

	$posts = isset($_GET['delete']) ? $_GET['delete'] : $_POST['delete'];

	// Nothing to do
	if (!isset($posts) || !$posts) {
		if ($loggedin)
			redirect(get_script_name().'?task=manage');
		else
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

	if (!$loggedin) {
		if (isset($_POST['password']) && $_POST['password'] !== '')
			make_error('Incorrect password for deletion.');

		// TODO: This is a stupid algorithm. Replace it.
		$hash = md5(md5($_POST['password']));
	}

	$rebuild_queue = array();
	$deleted_threads = array();

	$error = false;

	foreach ($posts as $id) {
		$post = PostByID($id);

		// Skip non-existent posts
		if ($post === false)
			continue;

		// Skip if parent is deleted
		if (isset($deleted_threads[$post['parent']]))
			continue;

		// Check password
		if (!$loggedin && $post['password'] !== $hash) {
			$error = true;
			continue;
		}

		deletePostByID($id);

		// Collect parents so we can rebuild or delete them
		if ($post['parent'])
			@$rebuild_queue[$post['parent']] = true;
		else
			$deleted_threads[$id] = true;
	}

	// Rebuild threads
	foreach ($rebuild_queue as $id)
		rebuildThread($id);
	rebuildIndexes();

	// Show an error if any posts had the incorrect password.
	if ($error)
		make_error('Incorrect password for deletion.');

	// TODO: Determine if we were deleting from the admin area or not.
	if ($loggedin)
		redirect(get_script_name().'?task=manage');
	else
		redirect(expand_path('index.html'));
}

function delete_post() {
	delete_get();
}
