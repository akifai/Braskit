<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit;

use lessc_fixed;

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

        foreach ($app['less.stylesheets'] as $key => $name) {
            $this->styles[$key] = array(
                'name' => $name,
                'path' => $key,
            );
        }

        $default = $app['less.default_style'];

        if ($default && $this->styles[$default]) {
            $this->default = $default;
        } elseif ($this->styles) {
            reset($this->styles);
            $this->default = key($this->styles);
        }
    }

    /**
     * Converts the style paths to web-accessible ones.
     */
    protected function transformPaths() {
        global $app;

        if ($this->pathsAreTransformed) {
            return;
        }

        foreach (array_keys($this->styles) as $key) {
            if (strstr($this->styles[$key]['path'], '/')) {
                continue; // don't modify this path
            }

            $style = $this->styles[$key]['path'];
            $path = $app['path.root']."/static/styles/$style";

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
            $path = expand_path("static/styles/$style/$basename");
            $this->styles[$key]['path'] = $path;
        }

        $this->pathsAreTransformed = true;
    }

    public function getDefault() {
        return $this->default;
    }

    /**
     * @return string Path for default style.
     */
    public function getDefaultPath() {
        if (!$this->pathsAreTransformed)
            $this->transformPaths();

        return $this->styles[$this->default]['path'];
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

        $less = new lessc_fixed();

        // strip whitespace if we aren't debugging
        if (!$app['less.debug']) {
            $less->setFormatter('compressed');
        }

        try {
            $less->compileFile($input, $output);
            return true;
        } catch (\Exception $e) {
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

/* vim: set ts=4 sw=4 sts=4 et: */
