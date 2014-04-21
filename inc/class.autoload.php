<?php

class AutoLoader {
	public static function register() {
		require_once('inc/lib/Twig/Autoloader.php');
		Twig_Autoloader::register();

		spl_autoload_register(array(new self, 'autoload'));
	}

	protected $prefixes = array(
		'View' => 'views/',
		'View_Install' => 'installer/',
	);

	protected $classes = array(
		'Ban' => 'class.ban.php',
		'BanCreate' => 'class.ban.php',
		'Board' => 'class.board.php',
		'BoardConfig' => 'class.config.php',
		'Cache' => 'class.cache.php',
		'Cache_APC' => 'class.cache.php',
		'Cache_Debug' => 'class.cache.php',
		'Cache_PHP' => 'class.cache.php',
		'Config' => 'class.config.php',
		'Database' => 'class.db.php',
		'DBConnection' => 'class.dbh.php',
		'DBStatement' => 'class.dbh.php',
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
		'Parser' => 'class.parser.php',
		'Parser_Block' => 'class.parser.php',
		'Parser_Inline' => 'class.parser.php',
		'Parser_Wakabamark' => 'class.parser.php',
		'Parser_Inline_Wakabamark' => 'class.parser.php',
		'Path' => 'class.router.php',
		'Path_QueryString' => 'class.router.php',
		'PgError' => 'class.pgerror.php',
		'PlainIB_Twig_Extension' => 'class.template.php',
		'PlainIB_Twig_Loader' => 'class.template.php',
		'Pimple' => 'lib/Pimple/Pimple.php',
		'Post' => 'class.post.php',
		'Request' => 'class.request.php',
		'Router' => 'class.router.php',
		'Router_Install' => 'class.router.php',
		'Router_Main' => 'class.router.php',
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
		'View' => 'class.view.php',
	);

	protected function autoload($class) {
		if (isset($this->classes[$class])) {
			require(TINYIB_ROOT.'/inc/'.$this->classes[$class]);

			return true;
		}

		$pos = strrpos($class, '_');

		if ($pos !== false) {
			$prefix = substr($class, 0, $pos);

			if (isset($this->prefixes[$prefix])) {
				$bit = $this->prefixes[$prefix];
				$file = strtolower(substr($class, $pos + 1));

				require(TINYIB_ROOT.'/inc/'.$bit.$file.'.php');

				return true;
			}
		}
	}
}
