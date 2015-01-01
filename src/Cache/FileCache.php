<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit\Cache;

/**
 * Cache stored in PHP files.
 */
class FileCache implements CacheInterface {
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

    /**
     * Unreliably checks if an object is stored in cache. Does not account for
     * race conditions, does not check expiry.
     */
    public function has($key) {
        $filename = $this->getFileName($key);

        return file_exists($key);
    }

    /**
     * {@inheritdoc}
     */
    public function get($key) {
        $filename = $this->getFileName($key);

        $cache = @include($filename);

        // We couldn't load the cache, or it expired
        if (!is_array($cache) || $cache['expired']) {
            // delete the file, if it exists
            @unlink($filename);

            return null;
        }

        return $cache['content'];
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value, $ttl = null) {
        if ($value === null) {
            throw new \InvalidArgumentException('Cached value cannot be NULL');
        }

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
        $deleted = @unlink($this->getFileName($key));

        if (!$deleted) {
            $error = error_get_last()['message'];
            throw new \RuntimeException("Couldn't delete file: $error");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function purge() {
        // get list of cache files
        $files = glob("$this->cacheDir/cache-*.php");

        // that didn't work for some reason
        if (!is_array($files)) {
            return;
        }

        foreach ($files as $file) {
            @unlink($file);
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
            throw new \RuntimeException("Couldn't create cache directory");
        }

        $this->dirExists = true;
    }
}
