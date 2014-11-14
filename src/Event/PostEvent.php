<?php
/*
 * Copyright (C) 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit\Event;

use Braskit\File;
use Braskit\Post;
use Symfony\Component\EventDispatcher\Event;

class PostEvent extends Event {
    /**
     * The post.before_insert event is dispatched before a post is inserted into
     * the database.
     *
     * @var string
     */
    const POST_BEFORE_INSERT = 'post.before_insert';

    /**
     * The post.after_insert event is dispatched after a post is inserted into
     * the database.
     *
     * @var string
     */
    const POST_AFTER_INSERT = 'post.after_insert';

    /**
     * @var Post
     */
    protected $post;

    /**
     * @var File
     */
    protected $file;

    /**
     * Constructor.
     *
     * @param Post $post
     * @param File $file
     */
    public function __construct(Post $post, File $file) {
        $this->post = $post;
        $this->file = $file;
    }

    /**
     * @return Post
     */
    public function getPost() {
        return $this->post;
    }

    /**
     * @return File
     */
    public function getFile() {
        return $this->file;
    }
}
