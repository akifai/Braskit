<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

/*
 * This file contains the default site-wide configuration.
 *
 * Webmasters: don't edit this file - you can change the settings from the
 * moderator panel.
 */

$dict = new Braskit\Config\Dictionary();

$dict->add('site_name', [
	'default' => 'Braskit Imageboard',
	'type' => 'text',
]);

$dict->add('home_url', [
	'default' => '/',
	'type' => 'text',
]);

$dict->add('latest_posts_count', [ 
	'default' => 25,
	'type' => 'number',
]);

return $dict;
