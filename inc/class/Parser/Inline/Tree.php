<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit\Parser\Inline;

/**
 * @author Stee
 */
class Tree {
    public $nodes = array();
    public $markup;
    public $parent;

    public function __construct($parent = false, $markup = false) {
        $this->markup = $markup;
        $this->parent = $parent;
    }

    public function add($node) {
        array_push($this->nodes, $node);
    }

    public function copyTo(Tree $tree) {
        foreach ($this->nodes as $node) {
            $tree->add($node);
        }
    }

    public function pop() {
        return array_pop($this->nodes);
    }
}

/* vim: set ts=4 sw=4 sts=4 et: */
