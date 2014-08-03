<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit\Thumb;

use Braskit\Image;
use Braskit\Thumb;

/**
 * Thumbnailing with imagemagick's convert utility.
 */
class Convert extends Thumb {
    protected $convertPath = 'convert';
    protected $thumbQuality = 75;

    public function makeThumbnail(Image $image, $max_w, $max_h) {
        $thumb = $this->createDataObject($image, $max_w, $max_h);

        if ($image->ext === 'gif') {
            /*
             * We use -sample for GIFs because it's fast.
             *
             * Using -sample on a 190-frame animated GIF takes 0.07 seconds
             * on my i5 system, unlike using -resize which takes 5.80
             * seconds.
             *
             * Using -coalesce has quite a huge impact on performance, but
             * it's necessary in order to not break animated thumbnails.
             * It's still way faster than -resize, so whatever.
             */
            $method = '-coalesce -sample';
        } else {
            $method = '-resize';
        }

        $cmd = sprintf("%s %s:%s %s '%dx%d!' -quality %d %s",
            $this->convertPath,
            $image->ext,
            escapeshellarg($image->getPath()),
            $method,
            $thumb->width,
            $thumb->height,
            $this->thumbQuality,
            escapeshellarg($thumb->tmpfile)
        );

        exec($cmd, $output, $error);

        if ($error) {
            return false;
        }

        return $thumb;
    }

    protected function setOptions(array $options) {
        if (isset($options['convert_path'])) {
            $this->convertPath = $options['convert_path'];
        }

        if (isset($options['quality'])) {
            $this->thumbQuality = $options['quality'];
        }
    }
}

/* vim: set ts=4 sw=4 sts=4 et: */
