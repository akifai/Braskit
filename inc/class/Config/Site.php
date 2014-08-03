<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

namespace Braskit\Config;

use Braskit\Config;

class Site extends Config {
    protected $standard_config = 'site_config.php';
    protected $cache_key = '_site_config';
}

/* vim: set ts=4 sw=4 sts=4 et: */
