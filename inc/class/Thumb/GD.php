<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit\Thumb;

use Braskit\Image;
use Braskit\Thumb;

class GD extends Thumb {
    protected $thumbQuality = 75;

    protected function makeThumbnail(Image $image, $max_w, $max_h) {
        $thumb = $this->createDataObject($image, $max_w, $max_h);
        $thumb->ext = 'jpg';

        $path = $image->getPath();

        switch ($image->ext) {
        case 'jpg':
            $source = imagecreatefromjpeg($path);
            break;
        case 'png':
            $source = imagecreatefrompng($path);
            break;
        case 'gif':
            $source = imagecreatefromgif($path);
            break;
        default:
            // shouldn't happen
            return false;
        }

        $output = imagecreatetruecolor($thumb->width, $thumb->height);

        imagecopyresampled($output, $source,
            0, 0, 0, 0, // some stupid coordinate shit
            $thumb->width, $thumb->height,
            $image->width, $image->height
        );

        // this creates the image
        $created = imagejpeg($output, $thumb->tmpfile, $this->thumbQuality);

        imagedestroy($source);
        imagedestroy($output);

        if (!$created) {
            return false;
        }

        return $thumb;
    }

    protected function setOptions(array $options) {
        if (isset($options['quality'])) {
            $this->thumbQuality = $options['quality'];
        }
    }
}

/* vim: set ts=4 sw=4 sts=4 et: */
