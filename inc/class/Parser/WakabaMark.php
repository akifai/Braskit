<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

/**
 * A parser for WakabaMark (http://wakaba.c3.cx/docs/docs.html#WakabaMark).
 *
 * Although we do comply with the spec, this differs from the reference
 * implementation (Wakaba/Kareha) in various ways:
 *
 *   - We don't have ^H for strikethrough. This is an easter egg in
 *     Wakaba/Kareha which causes so many problems it's not even worth adding.
 *   - Inline syntax (bold/italic/code) can actually be nested!
 *
 * There are probably a billion other little difference I'm unaware of or which
 * I forgot.
 */
class Parser_WakabaMark extends Parser_Block {
    const RE_PARAGRAPH = '/^\s*$/';
    const RE_LIST_UL = '/^([*+-])\s+/';
    const RE_LIST_OL = '/^(\d+)\.\s+/';
    const RE_PRETEXT = '/^(?: {4}|\t)(.*)/';
    const RE_QUOTE = '/^>(?!\>(?:\>\/[^\s\/]+\/)?\d+)/';

    protected $inParagraph = false;

    protected $syntax = array(
        // End of paragraph
        array(
            'regex' => self::RE_PARAGRAPH,
            'method' => false,
        ),

        // Unordered lists
        array(
            'regex' => self::RE_LIST_UL,
            'method' => 'doUnorderedList',
        ),

        // Ordered lists
        array(
            'regex' => self::RE_LIST_OL,
            'method' => 'doOrderedList'
        ),

        // Blockquote
        array(
            'regex' => self::RE_QUOTE,
            'method' => 'doQuote',
        ),

        // Preformatted text
        array(
            'regex' => self::RE_PRETEXT,
            'method' => 'doPreText',
        ),
    );

    // Normal text
    protected $fallbackMethod = 'doParagraph';

    // Close the open paragraph, if any.
    protected $endMethod = 'endParagraph';

    /**
     * Do parsing of inline stuff like bold and italic text.
     */
    protected function parseInline($text) {
        // yo dawg, parser in your parser, etc
        $parser = new Parser_Inline_WakabaMark($text, $this->modifiers);

        $this->parsed .= $parser->parsed;
        $this->stripped .= $parser->stripped;
    }

    protected function nextLine() {
        return isset($this->lines[0]) ? $this->lines[0] : false;
    }

    /**
     * Closes the current paragraph, if any.
     */
    protected function endParagraph() {
        if ($this->inParagraph) {
            $this->parsed .= '</p>';
            $this->inParagraph = false;
        }
    }

    /**
     * Parses unordered lists.
     */
    protected function doUnorderedList($line, $matches) {
        $this->parsed .= '<ul>';

        $token = $matches[1];

        // In wakaba, a new list is started if we change the bullet.
        // We emulate this behaviour.
        $regex = '/^'.preg_quote($token).'\s/';

        do {
            $this->parsed .= '<li>';

            // Remove bullet and trailing whitespace
            $line = trim(substr($line, strlen($token)));

            $this->parseInline($line);

            // Line breaks are allowed in list items if the next line has one or
            // more spaces at the beginning. Find those lines, parse them and
            // add them.
            while (
                preg_match('/^\s+[^\s]/', $this->nextLine()) &&
                $line = array_shift($this->lines)
            ) {
                $this->parsed .= '<br>';
                $this->parseInline(trim($line));
            }

            // End of list item
            $this->parsed .= '</li>';
        } while (
            // Find new list items
            preg_match($regex, $this->nextLine()) &&
            $line = array_shift($this->lines)
        );

        // End of list
        $this->parsed .= '</ul>';
    }

    /**
     * Parses ordered lists.
     */
    protected function doOrderedList($line, $matches) {
        $this->parsed .= '<ol>';

        do {
            $num = (int)$matches[1];

            // Strip line number away
            $line = preg_replace(self::RE_LIST_OL, '', $line);

            if (isset($next) && $num !== $next || !isset($next) && $num !== 1) {
                // Irregular list ordering
                $this->parsed .= '<li value="'.$num.'">';
            } else {
                $this->parsed .= '<li>';
            }

            $this->parseInline($line);

            // Line breaks
            while (
                preg_match('/^\s+[^\s]/', $this->nextLine()) &&
                $line = array_shift($this->lines)
            ) {
                $this->parsed .= '<br>';
                $this->parseInline(trim($line));
            }

            $this->parsed .= '</li>';

            // expected number for the next list item
            $next = $num + 1;
        } while (
            // Find new list items
            preg_match(self::RE_LIST_OL, $this->nextLine(), $matches) &&
            $line = array_shift($this->lines)
        );

        $this->parsed .= '</ol>';
    }

    /**
     * Parses sections with preformatted text.
     */
    protected function doPreText($line, $matches) {
        if (!strlen(trim($line))) {
            // ignore blank lines starting the code block
            return;
        }

        $this->parsed .= '<pre><code>';
        $this->parsed .= $this->escape($matches[1]);

        $breaks = 1;

        while (
            preg_match(self::RE_PRETEXT, $this->nextLine(), $matches) &&
            $line = array_shift($this->lines)
        ) {
            if (!strlen(trim($line))) {
                // never have trailing spaces
                ++$breaks;
            } else {
                $this->parsed .= str_repeat('<br>', $breaks);
                $this->parsed .= $this->escape($matches[1]);

                $breaks = 1;
            }
        }

        $this->parsed .= '</code></pre>';
    }

    /**
     * Parses quote sections.
     */
    protected function doQuote($line) {
        $this->parsed .= '<blockquote>';
        $this->parseInline($line);

        while (
            preg_match(self::RE_QUOTE, $this->nextLine()) &&
            $line = array_shift($this->lines)
        ) {
            $this->parsed .= '<br>';
            $this->parseInline($line);
        }

        $this->parsed .= '</blockquote>';
    }

    /**
     * Parses regular lines.
     *
     * @param $line string The line to be parsed.
     */
    protected function doParagraph($line) {
        if (!$this->inParagraph) {
            $this->parsed .= '<p>';
            $this->inParagraph = true;
        } else {
            $this->parsed .= '<br>';
        }

        $this->parseInline($line);
    }
}

/* vim: set ts=4 sw=4 sts=4 et: */
