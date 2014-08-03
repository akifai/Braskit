<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit;

use Image;

/**
 * Base class for image thumbnailing.
 */
abstract class Thumb {
    /**
     * Temporary directory.
     *
     * @var string
     */
    protected $tmp = '';

    /**
     * @param string $tmp    Temporary directory.
     * @param array $options Additional options.
     */
    public function __construct($tmp, Array $options = array()) {
        $this->tmp = $tmp;

        $this->setOptions($options);
    }

    /**
     * Creates a thumbnail.
     *
     * @param Image $image
     * @param $max
     *
     * @throws \RuntimeException if the image didn't thumbnail
     *
     * @return ThumbData
     */
    public function create(Image $image, $max_w = 200, $max_h = 200) {
        // try creating the thumbnail
        $thumb = $this->makeThumbnail($image, $max_w, $max_h);

        if ($thumb instanceof ThumbData) {
            return $thumb;
        }

        throw new \RuntimeException("Couldn't thumbnail image.");
    }

    /**
     * @param Image $image
     * @param integer $max_w
     * @param integer $max_h
     */
    protected function createDataObject($image, $max_w, $max_h) {
        // create container for thumbnail data
        $thumb = new ThumbData();

        // get output width/height
        $size = $this->getThumbSize(
            $image->width, $image->height,
            $max_w, $max_h
        );

        $thumb->width = $size['width'];
        $thumb->height = $size['height'];

        // get output filename
        $thumb->tmpfile = tempnam($this->tmp, 'bs');

        // assumption
        $thumb->ext = $image->ext;

        return $thumb;
    }

    /**
     * Creates a thumbnail.
     *
     * @param Image $image
     * @param integer $max_w
     * @param integer $max_h
     *
     * @return Thumb|boolean Thumb object if successful, or false on error.
     */
    abstract protected function makeThumbnail(Image $image, $max_w, $max_h);

    /**
     * Method which subclasses can override to take custom options.
     *
     * @param array
     */
    protected function setOptions(Array $options) { /* nothing! */ }

    /**
     * @param integer $width  Width of the full-size image
     * @param integer $height Height of the full-size image
     * @param integer $max_w  Maximum width of the thumbnail
     * @param integer $max_h  Maximum height of the thumbnail
     *
     * @return array  Desired W*H of the thumbnail
     */
    public function getThumbSize($width, $height, $max_w, $max_h) {
        if ($width > $max_w || $height > $max_h) {
            $height = (int)($height * $max_w / $width);
            $width = $max_w;

            if ($height > $max_h) {
                $width = (int)($width * $max_h / $height);
                $height = $max_h;
            }
        }

        return array(
            'width' => $width,
            'height' => $height,
        );
    }
}

/* vim: set ts=4 sw=4 sts=4 et: */
