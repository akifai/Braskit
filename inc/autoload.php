<?php
defined('TINYIB') or exit;

class AutoLoader {
	protected static $classes = array(
		'App' => 'class.app.php',
		'Board' => 'class.board.php',
		'BoardConfig' => 'class.config.php',
		'Config' => 'class.config.php',
		'Database' => 'class.db.php',
		'DBStatement' => 'class.db.php',
		'GlobalConfig' => 'class.config.php',
		'HTMLException' => 'class.exception.php',
		'IP' => 'class.IP.php',
		'JSMinPlus' => 'lib/jsminplus/jsminplus.php',
		'lessc' => 'lib/lessphp/lessc.inc.php',
		'lessc_fixed' => 'class.less.php',
		'PlainIB_Twig_Extension' => 'class.template.php',
		'PlainIB_Twig_Loader' => 'class.template.php',
		'RoutePathInfo' => 'class.app.php',
		'RouteQueryString' => 'class.app.php',
		'Spam' => 'class.spam.php',
		'Style' => 'class.less.php',
		'User' => 'class.user.php',
		'UserEdit' => 'class.user.php',
		'UserException' => 'class.exception.php',
		'UserCreate' => 'class.user.php',
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
