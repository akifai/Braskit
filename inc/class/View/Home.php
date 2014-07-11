<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

class View_Home extends View {
    protected function get() {
        diverge('/login');
    }
}

/* vim: set ts=4 sw=4 sts=4 et: */
