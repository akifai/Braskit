<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit;

/**
 * A representation of a file's metadata, the way it appears in the database.
 */
class FileMetaData {
    public $filename = '';
    public $width = 0;
    public $height = 0;
    public $size = 0;
    public $prettysize = '';
    public $md5 = '';
    public $shortname = '';
    public $origname = '';
    public $t_filename = '';
    public $t_width = 0;
    public $t_height = 0;
}

/* vim: set ts=4 sw=4 sts=4 et: */
