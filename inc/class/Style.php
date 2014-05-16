<?php

/**
 * @todo Restrict the methods available in templates.
 * @todo Provide a method for cleaning up old CSS caches.
 */
class Style {
	protected $pathsAreTransformed = false;
	protected $styles = array();
	protected $default;
	protected $json;

	const CACHE_KEY = 'style_obj';

	/**
	 * Get a cached version of a Style object with all the private vars set,
	 * or create a new one and cache it.
	 *
	 * @return Style
	 */
	public static function getObject() {
		global $app;

		$obj = $app['cache']->get(self::CACHE_KEY);

		if ($obj) {
			return $obj;
		}

		$obj = new self;
		$obj->transformPaths();

		$app['cache']->set(self::CACHE_KEY, $obj);

		return $obj;
	}

	public function __construct() {
		global $app;

		if ($app['less.stylesheets']) {
			$this->styles = $app['less.stylesheets'];
		} else {
			// # makes the <link> invalid, / prevents us from
			// "expanding" the path
			$this->styles = array('Default' => '#/');
		}

		if ($app['less.default_style']) {
			$this->default = $app['less.default_style'];
		} else {
			reset($this->styles);
			$this->default = current($this->styles);
		}
	}

	/**
	 * Converts the style paths to web-accessible ones.
	 */
	protected function transformPaths() {
		global $app;

		if ($this->pathsAreTransformed)
			return;

		foreach ($this->styles as &$style) {
			if (strpos($style, '/') !== false)
				continue; // don't modify this path

			$path = TINYIB_ROOT."/static/styles/$style";

			// look for compiled LESS
			$results = glob("$path/$style-*.css");

			if ($results && !$app['less.debug']) {
				// get the newest file
				$basename = basename(array_pop($results));
			} else {
				// make a new file
				$basename = $style.'-'.time().'.css';

				$input = "$path/$style.less";
				$output = "$path/$basename";

				$this->compileLess($input, $output);
			}

			// web path
			$style = expand_path("static/styles/$style/$basename");
		}

		$this->pathsAreTransformed = true;
	}

	/**
	 * @return string Path to default style.
	 */
	public function getDefault() {
		if (!$this->pathsAreTransformed)
			$this->transformPaths();

		return $this->styles[$this->default];
	}

	/**
	 * @return string JSON object with style names and their corresponding
	 *                paths.
	 */
	public function getJSON() {
		if (!$this->pathsAreTransformed)
			$this->transformPaths();

		if (!isset($this->json))
			$this->json = json_encode($this->styles, JSON_FORCE_OBJECT);

		return $this->json;
	}

	/**
	 * Compiles a given LESS file and stores it at the given location.
	 *
	 * @param string Path to LESS file.
	 * @param string Path to compiled CSS output.
	 * @return bool  Whether or not the compilation was successful.
	 */
	public static function compileLess($input, $output) {
		global $app;

		$less = new lessc_fixed;

		// strip whitespace if we aren't debugging
		if (!$app['less.debug']) {
			$less->setFormatter('compressed');
		}

		try {
			$less->compileFile($input, $output);
			return true;
		} catch (Exception $e) {
			// print to error log
			if (!$app['less.debug']) {
				file_put_contents(STDERR, "LESS: $message");
				return;
			}

			// print error to the file, as CSS comments
			$lines = explode("\n", $e->getMessage());
			$message = "/*\n * ".implode("\n * ", $lines)."\n */";

			file_put_contents($output, $message);

			return false;
		}
	}

	public static function __set_state($data) {
		$obj = new self;

		foreach ($data as $key => $value)
			$obj->$key = $value;

		return $obj;
	}
}

class lessc_fixed extends lessc {
	/*
	 * Fixes lazy variables.
	 * - https://github.com/leafo/lessphp/issues/302
	 * - https://github.com/ldbglobe/lessphp/compare/patch-1
	 */
	protected function sortProps($props, $split = false) {
		$vars = array();
		$imports = array();
		$other = array();

		foreach ($props as $prop) {
			switch ($prop[0]) {
			case "assign":
				if (isset($prop[1][0]) && $prop[1][0] == $this->vPrefix) {
					$vars[] = $prop;
				} else {
					$other[] = $prop;
				}
				break;
			case "import":
				$id = self::$nextImportId++;
				$prop[] = $id;
				$imports[] = $prop;
				$other[] = array("import_mixin", $id);
				break;
			default:
				$other[] = $prop;
			}
		}

		if ($split) {
			return array(array_merge($imports, $vars), $other);
		} else {
			return array_merge($imports, $vars, $other);
		}
	}
}
