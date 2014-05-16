<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

class AutoLoader {
	protected $includeDir = '';

	public static function register() {
		$inc = dirname(__FILE__);

		// autoloader for twig
		require_once("$inc/lib/Twig/Autoloader.php");
		Twig_Autoloader::register();

		// Stick other autoloaders here, kthx

		spl_autoload_register(array(new self($inc), 'autoload'));
	}

	protected $classes = array(
		// Libraries
		'JSMinPlus' => 'lib/jsminplus/jsminplus.php',
		'lessc' => 'lib/lessphp/lessc.inc.php',
		'Pimple' => 'lib/Pimple/Pimple.php',

		// Old shit
		'BanCreate' => 'class/Ban.php',
		'BoardConfig' => 'class/Config.php',
		'Braskit_Twig_Extension' => 'class/Template.php',
		'Braskit_Twig_Loader' => 'class/Template.php',
		'Cache_APC' => 'class/Cache.php',
		'Cache_Debug' => 'class/Cache.php',
		'Cache_PHP' => 'class/Cache.php',
		'DBStatement' => 'class/DBConnection.php',
		'FileDriver' => 'class/File.php',
		'FileMetaData' => 'class/File.php',
		'GlobalConfig' => 'class/Config.php',
		'HTMLException' => 'class/Exception.php',
		'ImageGIF' => 'class/Image.php',
		'ImageJPEG' => 'class/Image.php',
		'ImagePNG' => 'class/Image.php',
		'lessc_fixed' => 'class/Style.php',
		'ParamException' => 'class/Param.php',
		'Path' => 'class/Router.php',
		'Path_QueryString' => 'class/Router.php',
		'RequestException' => 'class/Request.php',
		'Router_Install' => 'class/Router.php',
		'Router_Main' => 'class/Router.php',
		'ThumbData' => 'class/Thumb.php',
		'ThumbException' => 'class/Thumb.php',
		'UserAdmin' => 'class/User.php',
		'UserCreate' => 'class/User.php',
		'UserEdit' => 'class/User.php',
		'UserException' => 'class/Exception.php',
		'UserLogin' => 'class/User.php',
		'UserNologin' => 'class/User.php',
	);

	public function __construct($inc) {
		$this->includeDir = $inc;

	}

	protected function autoload($class) {
		// check if the requested class is in the list of classes
		if (isset($this->classes[$class])) {
			// it is. load it.
			require $this->includeDir.'/'.$this->classes[$class];

			return;
		}

		// find the thing in the filesystem
		$classPath = str_replace(array('\\', '_'), '/', $class).'.php';
		$file = $this->includeDir.'/class/'.$classPath; 

		if (is_file($file)) {
			require $file;
		}
	}
}
