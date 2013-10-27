<?php
defined('TINYIB') or exit;

class AutoLoader {
	protected static $classes = array(
		'App' => 'class.app.php',
		'Ban' => 'class.ban.php',
		'BanCreate' => 'class.ban.php',
		'Board' => 'class.board.php',
		'BoardConfig' => 'class.config.php',
		'Config' => 'class.config.php',
		'Database' => 'class.db.php',
		'DBStatement' => 'class.db.php',
		'File' => 'class.file.php',
		'FileDriver' => 'class.file.php',
		'FileMetaData' => 'class.file.php',
		'GlobalConfig' => 'class.config.php',
		'HTMLException' => 'class.exception.php',
		'Image' => 'class.image.php',
		'ImageGIF' => 'class.image.php',
		'ImageJPEG' => 'class.image.php',
		'ImagePNG' => 'class.image.php',
		'IP' => 'class.IP.php',
		'JSMinPlus' => 'lib/jsminplus/jsminplus.php',
		'lessc' => 'lib/lessphp/lessc.inc.php',
		'lessc_fixed' => 'class.less.php',
		'PgError' => 'class.pgerror.php',
		'PlainIB_Twig_Extension' => 'class.template.php',
		'PlainIB_Twig_Loader' => 'class.template.php',
		'Post' => 'class.post.php',
		'RoutePathInfo' => 'class.app.php',
		'RouteQueryString' => 'class.app.php',
		'Spam' => 'class.spam.php',
		'Style' => 'class.less.php',
		'Thumb' => 'class.thumb.php',
		'ThumbConvert' => 'class.thumb.php',
		'ThumbGD' => 'class.thumb.php',
		'ThumbImagick' => 'class.thumb.php',
		'ThumbSips' => 'class.thumb.php',
		'User' => 'class.user.php',
		'UserAdmin' => 'class.user.php',
		'UserCreate' => 'class.user.php',
		'UserEdit' => 'class.user.php',
		'UserException' => 'class.exception.php',
		'UserLogin' => 'class.user.php',
		'UserNologin' => 'class.user.php',
	);

	public static function autoload($class) {
		if (!isset(self::$classes[$class]))
			return;

		$filename = TINYIB_ROOT.'/inc/'.self::$classes[$class];

		require($filename);

		return true;
	}
}

spl_autoload_register('AutoLoader::autoload');
