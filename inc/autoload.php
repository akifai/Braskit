<?php
defined('TINYIB') or exit;

class AutoLoader {
	protected static $classes = array(
		'Board' => 'class.board.php',
		'Database' => 'class.db.php',
		'DBStatement' => 'class.db.php',
		'IP' => 'class.IP.php',
		'TinyIB_Twig_Loader' => 'class.template.php',
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
