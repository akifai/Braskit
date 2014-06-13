<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit;

/**
 * Cache stored in PHP files.
 *
 * This is shit because it's a quick conversion of the old cache functions,
 * which were also shit.
 *
 * @todo Redo this.
 */
class Cache_PHP implements Cache {
    public function __construct($cacheDir) {
        $this->cacheDir = $cacheDir;
    }

    public function get($key) {
        $filename = "$this->cacheDir/cache-{$key}.php";

        @include($filename);

        // we couldn't load the cache
        if (!isset($cache) || $expired) {
            @unlink($filename);
            return false;
        }

        return $cache;
    }

    public function set($key, $value, $ttl = false) {
        @mkdir($this->cacheDir); // TODO ???

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

        writePage("$this->cacheDir/cache-$key.php", $content);

        return true;
    }

    public function delete($key) {
        return @unlink("$this->cacheDir/cache-$key.php");
    }

    public function purge() {
        // get list of cache files
        $files = glob("$this->cacheDir/cache-*.php");

        // that didn't work for some reason
        if (!is_array($files)) {
            return false;
        }

        foreach ($files as $file) {
            @unlink($file);
        }

        return true;
    }
}

/* vim: set ts=4 sw=4 sts=4 et: */
