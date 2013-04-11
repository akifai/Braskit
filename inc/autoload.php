<?php
defined('TINYIB') or exit;

class AutoLoader {
	protected static $classes = array(
		'Board' => 'class.board.php',
		'Config' => 'class.config.php',
		'Database' => 'class.db.php',
		'DBStatement' => 'class.db.php',
		'IP' => 'class.IP.php',
		'TaskLoader' => 'class.task.php',
		'TaskPathInfo' => 'class.task.php',
		'TaskQueryString' => 'class.task.php',
		'TinyIB_Twig_Loader' => 'class.template.php',
		'User' => 'class.user.php',
		'UserEdit' => 'class.user.php',
		'UserException' => 'class.user.php',
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
