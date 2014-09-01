<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit;

/*
 * Usage:
 *
 *   $upload = $request->getUpload('file');
 *
 *   // Throws an exception if the upload exists but is not valid.
 *   $file = new File($upload, "/path/to/file/directory");
 *
 *   // Doesn't throw exceptions
 *   $thumb = $file->thumb("/path/to/thumb/dir", 200, 200);
 *
 *   echo $file->filename;     // Destination file path
 *   echo $file->width;        // Image width
 *   echo $file->height;       // Image height
 *   echo $file->size;         // File size
 *   echo $file->md5;          // File checksum
 *   echo $file->origname;     // Original filename
 *   echo $thumb->t_filename;  // Destination thumbnail path
 *   echo $thumb->t_width;     // Thumbnail width
 *   echo $thumb->t_height;    // Thumbnail height
 *
 *   // Moves the temporary files into place.
 *   $file->move();
 */

class File extends FileMetaData {
    public $exists = true;

    // destination directories
    protected $dest;
    protected $t_dest;

    // the object used for detecting an image class
    protected $driver;

    // temporary filenames
    protected $tmp;
    protected $t_tmp;

    // extension of thumbnail
    public $t_ext;

    // A list of valid file extensions.
    public $filetypes = array('jpg', 'gif', 'png');

    // An array of methods for detecting the file type and properties. They
    // should return an object corresponding to the specific file type, or
    // false on failure.
    public $detectors = array(array('Braskit\\Image', 'detect'));

    /**
     * @todo
     */
    public function __construct($upload, $dest) {
        if (!is_array($upload)) {
            $this->exists = false;
            return;
        }

        // store arguments as properties
        $this->dest = $dest;

        // for convenience
        $this->tmp = $upload['tmp_name'];

        // analyses the file, sets the file type, etc
        $this->analyse();

        // creates the output filenames for both the main file and the
        // thumbnail
        $this->makeFileNames();

        $this->width = $this->driver->width;
        $this->height = $this->driver->height;
        $this->size = $upload['size'];
        $this->prettysize = make_size($this->size);
        $this->origname = basename($upload['name']);
        $this->shortname = shorten_filename($this->origname);
        $this->md5 = md5_file($upload['tmp_name']);
    }

    /**
     * Insert the file into the database.
     */
    public function insert(Post $post) {
        global $app;

        if ($this->exists) {
            $app['db']->insertFile($this, $post);
        }
    }

    /**
     * Move the temporary files to their correct destinations.
     * @todo
     */
    public function move($keep_filename = false /* TODO */) {
        if (!$this->exists) {
            // nothing to do
            return; 
        }

        $dest = $this->dest.'/'.$this->filename;

        // Move full image
        move_uploaded_file($this->tmp, $dest);

        chmod($this->dest.'/'.$this->filename, 0664);

        if (!$this->t_tmp)
            return;

        $t_dest = $this->t_dest.'/'.$this->t_filename;

        // move thumbnail
        rename($this->t_tmp, $t_dest);

        chmod($t_dest, 0664);
    }

    /**
     * Creates a thumbnail.
     */
    public function thumb($dest, $max_w, $max_h) {
        global $app;

        $this->t_dest = $dest;

        if (!$this->exists) {
            return false;
        }

        $path = $this->driver->getPath();

        if ($path === false) {
            return false;
        }

        try {
            // create thumbnail
            $thumb = $app['thumb']->create($this->driver);
        } catch (\Exception $e) {
            // thumbnail couldn't be created
            return false;
        }

        $this->t_tmp = $thumb->tmpfile;

        $this->t_width = $thumb->width;
        $this->t_height = $thumb->height;
        $this->t_filename .= $thumb->ext;

        return true;
    }

    /**
     * Detects a filetype.
     *
     * @throws Error if the file type is invalid
     */
    protected function analyse() {
        foreach ($this->detectors as $detector) {
            $obj = call_user_func($detector, $this->tmp);

            if ($obj && in_array($obj->ext, $this->filetypes)) {
                $this->driver = $obj;
                return true;
            }
        }

        throw new Error("Invalid file type.");
    }

    protected function makeFileNames() {
        // futaba timestamp
        $tim = time().substr(microtime(), 2, 3);

        // main file name
        $this->filename = "$tim.{$this->driver->ext}";

        // thumbnail name (extension comes later)
        $this->t_filename = "{$tim}s.";
    }
}

/* vim: set ts=4 sw=4 sts=4 et: */
