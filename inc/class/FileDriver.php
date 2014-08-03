<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit;

abstract class FileDriver {
    public $width = 0;
    public $height = 0;
    public $ext = false;

    protected $fileName;

    /**
     * Detects the file type and returns a new object of the same class if
     * the file type was recognised, or false on failure.
     *
     * @return FileDriver|boolean
     */
    public static function detect($filename) {
        return false;
    }

    /**
     * Returns the path of the image to thumbnail. For image uploads, the
     * path will be the same as the image. For audio uploads, this method
     * should attempt to extract an image, store it at a temporary location
     * and return that location. __destruct() can be used to remove the
     * temporary file afterwards.
     *
     * @return string|boolean Path of image to thumbnail, or false if none.
     */
    abstract public function getPath();
}

/* vim: set ts=4 sw=4 sts=4 et: */
