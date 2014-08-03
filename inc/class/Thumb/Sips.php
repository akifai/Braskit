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
 * Thumbnailing using the SIPS command line program in OS X.
 *
 * Should not be used in production and probably not in development either.
 */
class Sips extends Thumb {
    // taken from wakaba
    const CMD_F = 'sips -z %d %d -s formatOptions normal -s format jpeg %s --out %s >/dev/null';

    protected function makeThumbnail(Image $image, $max_w, $max_h) {
        $thumb = $this->createDataObject($image, $max_w, $max_h);
        $thumb->ext = 'jpg';

        $cmd = sprintf(self::CMD_F,
            $thumb->height, // yes, sips is stupid and uses h*w
            $thumb->width,
            escapeshellarg($image->getPath()),
            escapeshellarg($thumb->tmpfile)
        );

        exec($cmd, $output, $error);

        if ($error) {
            return false;
        }

        return $thumb;
    }
}

/* vim: set ts=4 sw=4 sts=4 et: */
