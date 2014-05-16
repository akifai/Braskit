<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

class Image extends FileDriver {
	public $width = 0;
	public $height = 0;

	public static function detect($filename) {
		$size = getimagesize($filename);

		if (!$size) {
			// couldn't identify type
			return false;
		}

		$ext = image_type_to_extension($size[2], false);

		// create an object corresponding to the current filetype or
		// return false
		$classname = 'Image'.strtoupper($ext);

		if (!class_exists($classname))
			return false;

		$obj = new $classname;

		$obj->fileName = $filename;
		$obj->width = $size[0];
		$obj->height = $size[1];

		return $obj;
	}

	public function getPath() {
		return $this->fileName;
	}
}

class ImageJPEG extends Image {
	public $ext = 'jpg';
}

class ImageGIF extends Image {
	public $ext = 'gif';
}

class ImagePNG extends Image {
	public $ext = 'png';
}
