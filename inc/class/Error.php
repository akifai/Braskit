<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit;

class Error extends \Exception {
    protected $htmlMessage;

    public function getHTMLMessage() {
        return $this->htmlMessage;
    }

    public function setHTMLMessage($message) {
        $this->htmlMessage = $message;
    }
}

/* vim: set ts=4 sw=4 sts=4 et: */
