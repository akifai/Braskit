<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit;

use Normalizer;

/**
 * Methods and properties which are common for all parsers.
 */
abstract class Parser {
    /**
     * The raw, normalised input.
     * @var string
     */
    public $raw = '';

    /**
     * The parsed input in HTML format.
     * @var string
     */
    public $parsed = '';

    /**
     * The text itself with no formatting or line breaks.
     * @var string
     */
    public $stripped = '';

    /**
     * Removes characters which are undesirable in all circumstances, such as
     * control codes and invalid Unicode sequences.
     */
    public static function normaliseInput($text) {
        // remove illegal characters
        $text = preg_replace(
            '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/',
            '',
            $text
        );

        // remove invalid unicode
        $text = htmlspecialchars_decode(
            htmlspecialchars($text, ENT_SUBSTITUTE, 'UTF-8')
        );

        // normalise unicode
        if (extension_loaded('intl') && !Normalizer::isNormalized($text)) {
            $text = Normalizer::normalize($text);
        }

        return $text;
    }

    /**
     * Escapes HTML characters in a string.
     *
     * @param $str string
     * @return string
     */
    public static function escape($str) {
        return htmlspecialchars($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}

/* vim: set ts=4 sw=4 sts=4 et: */
