<?php

interface Cache {
	public function get($key);
	public function set($key, $value, $ttl = false);
	public function delete($key);
	public function purge();
}

class Cache_Debug implements Cache {
	public function get($key) {
		return false;
	}

	public function set($key, $value, $timeout = false) {
		return false;
	}

	public function delete($key) {
		return true;
	}

	public function purge() {
		return true;
	}
}

class Cache_APC implements Cache {
	public function get($key) {
		return apc_fetch($key);
	}

	public function set($key, $value, $timeout = false) {
		return apc_add($key, $value, $ttl);
	}

	public function delete($key) {
		return apc_delete($key);
	}

	/**
	 * @todo shared cache, etc
	 */
	public function purge() {
		return apc_clear_cache('user');
	}
}

/**
 * Cache stored in PHP files.
 *
 * This is shit because it's a quick conversion of the old cache functions,
 * which were also shit.
 *
 * @todo Redo this.
 */
class Cache_PHP implements Cache {
	public function get($key) {
		global $cache_dir;

		$filename = "$cache_dir/cache-{$key}.php";

		@include($filename);

		// we couldn't load the cache
		if (!isset($cache) || $expired) {
			@unlink($filename);
			return false;
		}

		return $cache;
	}

	public function set($key, $value, $ttl = false) {
		global $cache_dir;

		@mkdir('cache'); // FIXME

		// Content of the cache file
		$content = '<?php ';

		if ($ttl) {
			$eol = time() + $ttl; // end of life for cache
			$content .= sprintf('$expired = time() > %d;', $eol);
		} else {
			// the cache never expires
			$content .= '$expired = false;';
		}

		$dumped_data = var_export($value, true);
		$content .= sprintf('$cache = %s;', $dumped_data);

		writePage("$cache_dir/cache-$key.php", $content);

		return true;
	}

	public function delete($key) {
		global $cache_dir;

		return @unlink("$cache_dir/cache-$key.php");
	}

	public function purge() {
		global $cache_dir;

		// get list of cache files
		$files = glob("$cache_dir/cache-*.php");

		// that didn't work for some reason
		if (!is_array($files))
			return false;

		foreach ($files as $file)
			unlink($file);

		return true;
	}
}
