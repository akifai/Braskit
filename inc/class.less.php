<?php
defined('TINYIB') or exit;

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
