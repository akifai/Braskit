<?php
defined('TINYIB') or exit;

/*
 * This file contains the default site-wide configuration.
 *
 * Don't edit this file - the settings can be changed from the moderator panel.
 *
 * Context: inc/class.config.php
 */

$this->config['site_name'] = array(
	'value' => 'PlainIB Imageboard',
	'type' => 'string',
	'description' => 'The name of the site.',
);

$this->config['home_url'] = array(
	'value' => '/',
	'type' => 'string',
	'description' => 'URL of the home page',
);

$this->config['latest_posts_count'] = array(
	'value' => 25,
	'type' => 'integer',
	'description' => 'Number of latest posts to show in the dashboard.',
);
