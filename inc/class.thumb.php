<?php

/*
 * Usage:
 *
 *   $thumb = Thumb::getObject("/path/to/source.jpg", $image); // Image object
 *
 *   $thumbPath = $thumb->setMax(200, 200)->create();
 */

abstract class Thumb {
	public $width = 0;
	public $height = 0;

	// extension of thumbnail
	public $ext;

	protected $sourceFile;
	protected $maxWidth = 200;
	protected $maxHeight = 200;

	protected $image;

	public function __construct($source, $image = false) {
		global $temp_dir;

		if (!is_readable($source))
			throw new Exception("Cannot read the image.");

		if ($image instanceof Image) {
			$this->image = $image;
		} else {
			$this->image = Image::detect($source);

			if (!$this->image) {
				// TODO: Fail silently
				throw new Exception("Trying to thumbnail non-image file.");
			}
		}

		$this->sourceFile = $source;
		$this->tempnam = tempnam($temp_dir, 'plainib_');
	}

	public function setMax($width, $height) {
		if ($width >= 16 && $height >= 16) {
			$this->maxWidth = (int)$width;
			$this->maxHeight = (int)$height;

			return $this;
		}

		throw new Exception('Max dimensions must be at least 16x16 pixels.');
	}

	protected function makeThumbSize() {
		$width = $this->image->width;
		$height = $this->image->height;

		$max_w = $this->maxWidth;
		$max_h = $this->maxHeight;

		if ($width <= $max_w && $height <= $max_h) {
			$this->width = $width;
			$this->height = $height;

			return;
		}

		$this->width = $max_w;
		$this->height = (int)($height * $max_w / $width);

		if ($this->height > $max_h) {
			$this->width = (int)($width * $max_h / $height);
			$this->height = $max_h;
		}
	}

	/**
	 * @return boolean Whether or not this class will work in the current
	 *                 environment. For instance, calling this from the
	 *                 ThumbImagick subclass on a system without the Imagick
	 *                 library would return false.
	 */
	public static function test() {
		return false;
	}

	/**
	 * Creates a thumbnail and saves it at the specified location.
	 *
	 * @param string Destination of thumbnail.
	 */
	abstract public function create();

	/**
	 * @return Thumb A new Thumb object corresponding to the configured
	 *               method defined in the configuration.
	 */
	public static function getObject($src, $image) {
		global $thumb_method;

		switch ($thumb_method) {
		case 'convert':
			$obj = new ThumbConvert($src, $image);
			break;
		case 'gd':
			$obj = new ThumbGD($src, $image);
			break;
		case 'imagemagick':
		case 'imagick':
			$obj = new ThumbImagick($src, $image);
			break;
		case 'sips':
			$obj = new ThumbSips($src, $image);
			break;
		default:
			if (class_exists($thumb_method))
				$obj = new $thumb_method($src, $image);
			else
				throw new LogicException("Unknown thumbnail method '$thumb_method'.");
		}

		return $obj;
	}
}

class ThumbConvert extends Thumb {
	private static $convertPath;

	public function create() {
		$this->makeThumbSize();

		if (!isset(self::$convertPath))
			self::findExecutable();

		if (!self::$convertPath)
			return false; // convert not found

		// Inherit the original image's extension. We'll need to change
		// this when adding support for screwy image formats.
		$this->ext = $this->image->ext;

		$method = '-resize';

		// We use -sample for GIFs because it's fast.
		//
		// Using -sample on a 190-frame animated GIF takes 0.07 seconds
		// on my i5 system, unlike using -resize which takes 5.80
		// seconds.
		//
		// Using -coalesce has quite a huge impact on performance, but
		// it's necessary in order to not break animated thumbnails.
		// It's still way faster than -resize, so whatever.
		if ($this->image->ext === 'gif')
			$method = '-coalesce -sample';

		$cmd = sprintf("%s %s:%s %s '%dx%d!' -quality 75 %s",
			self::$convertPath,
			$this->image->ext,
			escapeshellarg($this->sourceFile),
			$method,
			$this->width,
			$this->height,
			escapeshellarg($this->tempnam)
		);

		exec($cmd, $output, $exit);

		if (!$exit) {
			// success
			return $this->tempnam;
		}

		return false;
	}

	public static function test() {
		if (!isset(self::$convertPath))
			self::findExecutable();

		return self::$convertPath !== false;
	}

	private static function findExecutable() {
		// the usual suspects
		$paths = array(
			'/usr/local/bin/convert',
			'/usr/bin/convert',
			getenv('HOME').'/bin/convert',
		);

		foreach ($paths as $convert) {
			if (is_executable($convert)) {
				self::$convertPath = $convert;

				return $convert;
			}
		}

		// use `which' to look for the command
		$path = shell_exec('which convert');

		if ($path && substr($path, 0, 1) === '/') {
			self::$convertPath = trim($path);

			return;
		}

		self::$convertPath = false;
	}
}

class ThumbGD extends Thumb {
	private $thumb;
	private $source;

	public $ext = 'jpg';

	public function create() {
		$this->makeThumbSize();

		switch ($this->image->ext) {
		case 'jpg':
			$this->source = imagecreatefromjpeg($this->sourceFile);
			break;
		case 'png':
			$this->source = imagecreatefrompng($this->sourceFile);
			break;
		case 'gif':
			$this->source = imagecreatefromgif($this->sourceFile);
			break;
		default:
			// shouldn't happen
			return false;
		}

		$this->thumb = imagecreatetruecolor($this->width, $this->height);

		imagecopyresampled(
			$this->thumb,  // new image
			$this->source, // source image
			0, 0, 0, 0,    // some stupid coordinate shit
			$this->width, $this->height, // new w*h
			$this->image->width, $this->image->height // old w*h
		);

		// this creates the image
		if (imagejpeg($this->thumb, $this->tempnam))
			return $this->tempnam;

		return false;
	}

	public function __destruct() {
		imagedestroy($this->source);
		imagedestroy($this->thumb);
	}

	public static function test() {
		return extension_loaded('gd');
	}
}

class ThumbImagick extends Thumb {
	public function create() {
		$this->makeThumbSize();

		throw new LogicException("Not implemented");
	}

	public static function test() {
		return class_exists('Imagick');
	}
}

class ThumbSips extends Thumb {
	public $ext = 'jpg';

	// stolen from wakaba - this is only for OS X
	const CMD_F = 'sips -z %d %d -s formatOptions normal -s format jpeg %s --out %s >/dev/null';

	public function create() {
		$this->makeThumbSize();

		$cmd = sprintf(self::CMD_F,
			$this->height, // yes, sips is stupid and uses h*w
			$this->width,
			escapeshellarg($this->sourceFile),
			escapeshellarg($this->tempnam)
		);

		exec($cmd, $output, $exit);

		if (!$exit) {
			// success
			return $this->tempnam;
		}

		return false;
	}

	public static function test() {
		return is_executable('/usr/bin/sips');
	}
}
