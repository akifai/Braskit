<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

/**
 * Abstract class which provides the facilities needed for parsing blocks of
 * text.
 */
abstract class Parser_Block extends Parser {
    protected $lines = array();

    /**
     * A list of callables which perform substitutions on the text nodes
     * prior to appending them to the parsed output. Typically used for
     * linkifying citations and stuff like that.
     * @var array
     */
    protected $modifiers = array();

    /**
     * A definition of the block syntax. See Parser_WakabaMark for an example.
     * @var array
     */
    protected $syntax = array();

    /**
     * The method to execute when no "special" syntax matched the line.
     * @var string
     */
    protected $fallbackMethod;

    /**
     * The method to call when the parsing is done. Typically used for closing
     * any elements which are left open.
     * @var string
     */
    protected $endMethod;

    public function __construct($text, Array $textModifiers = array()) {
        $this->modifiers = $textModifiers;

        $this->raw = $this->normaliseInput($text);

        if (!strlen(trim($this->raw)))
            return;

        $this->parse();
    }

    protected function parse() {
        // Split lines
        $this->lines = preg_split('/\r?\n|\r/', $this->raw);

        // Loop through all the lines
        while (($line = array_shift($this->lines)) !== null) {
            // Loop through all the different syntax types
            foreach ($this->syntax as $node) {
                if (!$node['regex']) {
                    // end the fallback state
                    $this->{$this->endMethod}();

                    // no regex, do the callback
                    $this->{$node['method']}($line);

                    // skip to next line
                    continue 2;
                } elseif (preg_match($node['regex'], $line, $matches)) {
                    // end the fallback state
                    $this->{$this->endMethod}();

                    // there was a regex and it matched the current line
                    if ($node['method']) {
                        $this->{$node['method']}($line, $matches);
                    }

                    // skip to next line
                    continue 2;
                }
            }

            // no syntax matched, do the fallback
            $this->{$this->fallbackMethod}($line);
        }

        $this->{$this->endMethod}();

        $this->stripped = trim($this->stripped);
    }
}

/* vim: set ts=4 sw=4 sts=4 et: */
