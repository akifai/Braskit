<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit\Parser\Inline;

use Braskit\Parser\Inline;

class WakabaMark extends Inline {
    protected $markup = array(
        array(
            'token' => array('*', '_'),
            'format' => array('<em>', '</em>'),
            'children' => true,
        ),
        array(
            'token' => array('**', '__'),
            'format' => array('<strong>', '</strong>'),
            'children' => true,
        ),
        array(
            'token' => array('`+'),
            'format' => array('<code>', '</code>'),
            'callback' => 'trim',
            'children' => false,
        ),
    );
}

/* vim: set ts=4 sw=4 sts=4 et: */
