<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit;

class Image extends FileDriver {
    public $width = 0;
    public $height = 0;

    public static function detect($filename) {
        $size = getimagesize($filename);

        if (!$size) {
            // couldn't identify type
            return false;
        }

        $ext = image_type_to_extension($size[2], false);

        // create an object corresponding to the current filetype or
        // return false
        $classname = 'Braskit\\Image\\'.strtoupper($ext);

        if (!class_exists($classname))
            return false;

        $obj = new $classname;

        $obj->fileName = $filename;
        $obj->width = $size[0];
        $obj->height = $size[1];

        return $obj;
    }

    public function getPath() {
        return $this->fileName;
    }
}

/* vim: set ts=4 sw=4 sts=4 et: */
