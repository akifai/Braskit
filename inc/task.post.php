<?php
defined('TINYIB') or exit;

// FIXME TODO XXX THIS IS ALL SHIT AND HAS TO BE REWRITTEN

function post_post() {
	$loggedin = check_login();
	$rawpost = isRawPost();
	if (!$loggedin) {
		checkBanned();
		checkMessageSize();
		checkFlood();
	}

	$post = newPost(setParent());
	$post['ip'] = $_SERVER['REMOTE_ADDR'];

	list($post['name'], $post['tripcode']) = make_name_tripcode($_POST['field1']);

	$post['name'] = substr($post['name'], 0, 75);
	$post['email'] = substr($_POST['field2'], 0, 75);
	$post['subject'] = substr($_POST['field3'], 0, 75);

	if ($rawpost) {
		//$rawposttext = ($isadmin) ? ' <span style="color: red;">## Admin</span>' : ' <span style="color: purple;">## Mod</span>';
		$post['message'] = $_POST['field4']; // Treat message as raw HTML
	} else {
		$rawposttext = '';
		$post['message'] = str_replace("\n", '<br>', colorQuote(postLink(cleanString(rtrim($_POST['field4'])))));
	}

	$post['password'] = ($_POST['password'] != '') ? md5(md5($_POST['password'])) : '';
	$post['date'] = make_date(time());

	if (isset($_FILES['file']) && $_FILES['file']['name'] != "") {
		require 'inc/image.php';

		validateFileUpload();

		$tmp_name = $_FILES['file']['tmp_name'];

		if (!is_uploaded_file($tmp_name))
			make_error("File transfer failure. Please retry the submission.");

		if ((TINYIB_MAXKB > 0) && (filesize($tmp_name) > (TINYIB_MAXKB * 1024)))
			make_error("That file is larger than " . TINYIB_MAXKBDESC . ".");

		if (($info = analyse_image($tmp_name)) === false)
			make_error("Failed to read the size of the uploaded file. Please retry the submission.");

		$post['file_original'] = $_FILES['file']['name'];
		$post['file_hex'] = md5_file($tmp_name);
		$post['file_size'] = $_FILES['file']['size'];
		$post['file_size_formatted'] = convertBytes($post['file_size']);
		$file_name = time().substr(microtime(), 2, 3);
		$post['file'] = sprintf('%s.%s', $file_name, $info['ext']);
		$post['thumb'] = sprintf('%ss.%s', $file_name, $info['ext']);

		if (!in_array($info['ext'], array('jpg', 'gif', 'png')))
			make_error("Only GIF, JPG, and PNG files are allowed.");

		$file_location = "src/" . $post['file'];
		$thumb_location = "thumb/" . $post['thumb'];

		checkDuplicateImage($post['file_hex']);

		if (!move_uploaded_file($tmp_name, $file_location))
			make_error("Could not copy uploaded file.");

		$thumb_size = make_thumb_size(
			$info['width'],
			$info['height'],
			TINYIB_MAXW,
			TINYIB_MAXH
		);

		if ($thumb_size === false) {
			copy($file_location, $thumb_location);
			$post['thumb_width'] = $info['width'];
			$post['thumb_height'] = $info['height'];
		} else {
			list($thumb_w, $thumb_h) = $thumb_size;
			if (!createThumbnail($file_location, $thumb_location, $thumb_w, $thumb_h)) {
				@unlink($file_location);
				make_error("Could not create thumbnail.");
			}

			$post['thumb_width'] = $thumb_w;
			$post['thumb_height'] = $thumb_h;
		}

		$post['image_width'] = $info['width'];
		$post['image_height'] = $info['height'];
	}

	if ($post['file'] == '') { // No file uploaded
		if (!$post['parent']) {
			make_error("An image is required to start a thread.");
		}
		if (str_replace('<br>', '', $post['message']) == "") {
			make_error("Please enter a message and/or upload an image to make a reply.");
		}
	} else {
		echo $post['file_original'] . ' uploaded.<br>';
	}

	$post['id'] = insertPost($post);

	trimThreads();

	if ($post['parent']) {
		rebuildThread($post['parent']);

		if (strtolower($post['email']) != 'sage')
			bumpThreadByID($post['parent']);
	} else {
		rebuildThread($post['id']);
	}

	rebuildIndexes();
	if (strtolower($post['email']) == 'noko')
		$dest = 'res/'.(!$post['parent'] ? $post['id'] : $post['parent']).'.html#'.$post['id'];
	else
		$dest = 'index.html';

	redirect(expand_path($dest));
}
