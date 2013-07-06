<?php
defined('TINYIB') or exit;

function analyse_image($filename) {
	// is this necessary? php docs say getimagesize() doesn't need GD
	if (function_exists('getimagesize'))
		return gd_analyse_image($filename);

	// Plain PHP detection
	$fh = fopen($filename, 'rb');

	if (is_array($info = analyse_jpeg($fh)))
		return $info;
	if (is_array($info = analyse_gif($fh)))
		return $info;
	if (is_array($info = analyse_png($fh)))
		return $info;

	fclose($fh);
	return false;
}

function gd_analyse_image($filename) {
	if (($size = getimagesize($filename)) === false)
		return false; // couldn't identify type

	$filetype = image_type_to_extension($size[2], false);

	// .jpeg is a retarded extension. Let's not use it.
	if ($filetype === 'jpeg')
		$filetype = 'jpg';

	return array(
		'ext' => $filetype,
		'width' => $size[0],
		'height' => $size[1],
	);
}

/*
 * Plain-PHP image analysing. Ported from Wakaba.
 * These functions MUST rewind/close the file handle as appropriate.
 */

function analyse_jpeg($fh) {
	if (($buf = fread($fh, 2)) !== "\xFF\xD8") {
		rewind($fh);
		return false;
	}

	while (true) {
		while (true) {
			if (($buf = fread($fh, 1)) === false)
				break 2;
			if ($buf === "\xFF")
				break;
		}

		$p = ftell($fh);
		if (($buf = fread($fh, 3)) === false || ftell($fh)-$p !== 3)
			break;

		list($mark, $size) = array_values(unpack('Ca/nb', $buf));

		if ($mark === 0xDA || $mark === 0xD9)
			break; // SOS/EOI

		// MS GDI+ JPEG exploit uses short chunks
		if ($size < 2) {
			fclose($fh);
			throw new Exception('The uploaded file is possibly malicious.');
		}

		if ($mark >= 0xC0 && $mark <= 0xC2) {
			$p = ftell($fh);
			if (($buf = fread($fh, 5)) === false || ftell($fh) - $p !== 5)
				break;

			fclose($fh);

			$values = array_values(unpack('Ca/nb/nc', $buf));
			list(/*$bits*/, $height, $width) = $values;

			return array(
				'ext' => 'jpg',
				'width' => $width,
				'height' => $height,
			);
		}

		fseek($fh, $size - 2, 1);
	}

	rewind($fh);
	return false;
}

function analyse_gif($fh) {
	$buf = fread($fh, 10);
	$bytes = ftell($fh);

	if ($bytes !== 10)
		return false;

	list($magic, $width, $height) = array_values(unpack('A6a/vb/vc', $buf));

	if (!($magic === 'GIF87a' || $magic === 'GIF89a')) {
		rewind($fh);
		return false;
	}

	fclose($fh);

	return array(
		'ext' => 'gif',
		'width' => $width,
		'height' => $height
	);
}

function analyse_png($fh) {
	$buf = fread($fh, 24);
	$bytes = ftell($fh);

	if ($bytes !== 24)
		return false;

	$values = array_values(unpack('Na/Nb/Nc/Nd/Ne/Nf', $buf));
	list($magic1, $magic2, /*$length*/, $ihdr, $width, $height) = $values;

	// PHP doesn't support unsigned integers, which is why we need the
	// sprintf() crap.
	if (!(sprintf('%u', $magic1) === strval(0x89504E47)
	&& $magic2 === 0x0D0A1A0A && $ihdr === 0x49484452)) {
		rewind($fh);
		return false;
	}

	fclose($fh);
	return array(
		'ext' => 'png',
		'width' => $width,
		'height' => $height,
	);
}
