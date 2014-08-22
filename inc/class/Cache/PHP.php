<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit\Cache;

use Braskit\Cache;

/**
 * Cache stored in PHP files.
 */
class PHP implements Cache {
    /**
     * Cache directory.
     *
     * @var string
     */
    protected $cacheDir = '';

    /**
     * Cache directory exists.
     *
     * @var boolean
     */
    protected $dirExists = false;

    /**
     * Constructor.
     *
     * @param string $cacheDir Directory to cache to.
     */
    public function __construct($cacheDir) {
        $this->cacheDir = $cacheDir;
    }

    public function get($key) {
        $filename = $this->getFileName($key);

        $cache = @include($filename);

        // We couldn't load the cache, or it expired. Delete it.
        if (!is_array($cache) || $cache['expired']) {
            @unlink($filename);

            return false;
        }

        return $cache['content'];
    }

    public function set($key, $value, $ttl = false) {
        $this->createDirectory();

        // Content of the cache file
        $content = '<?php return array(';

        if ($ttl) {
            $expired = sprintf('time() > %d', time() + $ttl);
        } else {
            // the cache never expires
            $expired = 'false';
        }

        $content .= sprintf("'expired' => $expired, ");

        $data = var_export($value, true);
        $content .= sprintf("'content' => %s", $data);

        $content .= ');';

        writePage($this->getFileName($key), $content);
    }

    public function delete($key) {
        unlink($this->getFileName($key));
    }

    public function purge() {
        // get list of cache files
        $files = glob("$this->cacheDir/cache-*.php");

        // that didn't work for some reason
        if (!is_array($files)) {
            return false;
        }

        foreach ($files as $file) {
            unlink($file);
        }
    }

    /**
     * Get the filename of a cache object.
     *
     * @param string $key Cache key.
     * @return string     File name.
     */
    protected function getFileName($key) {
        return $this->cacheDir.'/cache-'.md5($key).'.php';
    }

    /**
     * Ensures that the cache directory exists. Should be called when writing to
     * cache.
     *
     * @throws \RuntimeException if the cache directory cannot be created.
     */
    protected function createDirectory() {
        if ($this->dirExists || is_dir($this->cacheDir)) {
            return;
        }

        $created = @mkdir($this->cacheDir, 0777 & ~umask(), true);

        if (!$created) {
            throw new \RuntimeException("Couldn't create cache directory.");
        }

        $this->dirExists = true;
    }
}

/* vim: set ts=4 sw=4 sts=4 et: */
