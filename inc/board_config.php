<?php
defined('TINYIB') or exit;

/*
 * This file contains the default configuration for individual boards.
 *
 * Don't edit this file - the settings can be changed from the moderator panel.
 *
 * Context: inc/class.config.php
 */

$this->config['allow_thread_textonly'] = array(
	'value' => false,
	'type' => 'boolean',
	'description' => 'Allow posting new threads with text only.',
);

$this->config['allow_thread_images'] = array(
	'value' => true,
	'type' => 'boolean',
	'description' => 'Allow posting new threads with images.',
); // TODO

$this->config['allow_reply_textonly'] = array(
	'value' => true,
	'type' => 'boolean',
	'description' => 'Allow posting text-only replies.',
); // TODO

$this->config['allow_reply_images'] = array(
	'value' => true,
	'type' => 'boolean',
	'description' => 'Allow posting replies with images.',
); // TODO

$this->config['max_thumb_w'] = array(
	'value' => 200,
	'type' => 'integer',
	'description' => 'Maximum thumbnail width.',
);

$this->config['max_thumb_h'] = array(
	'value' => 200,
	'type' => 'integer',
	'description' => 'Maximum thumbnail height.',
);

$this->config['threads_per_page'] = array(
	'value' => 10,
	'type' => 'integer',
	'description' => 'How many threads to show per page.',
);

$this->config['replies_shown'] = array(
	'value' => 5,
	'type' => 'integer',
	'description' => 'How many replies per thread to show on the board indexes.',
);

$this->config['max_threads'] = array(
	'value' => 100,
	'type' => 'integer',
	'description' => 'Maximum number of threads on the board.',
);

$this->config['max_kb'] = array(
	'value' => 3000,
	'type' => 'integer',
	'description' => 'The maximum size of uploaded files, in kilobytes.',
);

$this->config['default_name'] = array(
	'value' => "Anonymous",
	'type' => 'string',
	'description' => 'Default name.',
);

$this->config['default_subject'] = array(
	'value' => "",
	'type' => 'string',
	'description' => 'Default subject.',
);

$this->config['default_comment'] = array(
	'value' => "",
	'type' => 'string',
	'description' => 'Default comment.',
);

$this->config['forced_anon'] = array(
	'value' => false,
	'type' => 'boolean',
	'description' => 'Disable the name and email fields.',
);

$this->config['allow_tripcodes'] = array(
	'value' => true,
	'type' => 'boolean',
	'description' => "Allows tripcodes to be used when posting. If disabled, tripkeys will be stripped away rather than being interpreted as part of the name. Requires 'forced_anon' to be turned off.",
);

$this->config['allow_email'] = array(
	'value' => false,
	'type' => 'boolean',
	'description' => "Show the email field in the postform. Requires 'forced_anon' to be turned off.",
);

$this->config['allow_sage'] = array(
	'value' => true,
	'type' => 'boolean',
	'description' => 'Allows replies with "sage" in the email field to let the thread remain unbumped. If \'allow_email\' is turned off or \'forced_anon\' is turned on, a "sage" checkbox will be shown to the user instead.',
);

$this->config['auto_noko'] = array(
	'value' => true,
	'type' => 'boolean',
	'description' => 'Return to the thread after posting.',
);

$this->config['display_id'] = array(
	'value' => false,
	'type' => 'boolean',
	'description' => "Displays a poster's unique ID next to each post's timestamp.",
); // TODO

$this->config['enable_autobans'] = array(
	'value' => true,
	'type' => 'boolean',
	'description' => "Enables automatic banning of IPs which trigger the spamtrap or post comments containing spam. It is recommended to enable 'check_referrer' when using this.",
);

$this->config['autoban_seconds'] = array(
	'value' => 21600, // 6 hours
	'type' => 'integer',
	'description' => 'Number of seconds an autoban should last.',
);

$this->config['spam_trap'] = array(
	'value' => true,
	'type' => 'boolean',
	'description' => "Enables the spamtrap, a set of hidden input fields which act as a honeypot to catch spambots. In general, there is no reason to leave this off.",
); // TODO

$this->config['autoban_trap_message'] = array(
	'value' => 'Triggered a spam trap (auto-banned)',
	'type' => 'string',
	'description' => 'Ban description for IPs that triggered the spamtrap.',
); // TODO

$this->config['check_spam'] = array(
	'value' => true,
	'type' => 'boolean',
	'description' => 'Check if a post contains any strings defined in the spam filter.',
);

$this->config['autoban_spam_message'] = array(
	'value' => 'Auto-banned: "%s"',
	'type' => 'string',
	'description' => 'Ban description for IPs that are auto-banned for spam. "%s" gets replaced with the string which caused the ban.',
);

$this->config['check_referrer'] = array(
	'value' => true,
	'type' => 'boolean',
	'description' => 'Enables referrer checking when posting. This check ensures that the referring hostname is equivalent to the hostname of the site. It is highly recommended to leave it on, unless a broken server configuration causes problems with it.',
); // TODO

$this->config['check_referrer_strict'] = array(
	'value' => false,
	'type' => 'boolean',
	'description' => "Enables stricter referrer checking when posting. This might block certain poorly made bots, but could also block users with special userscripts or buggy browsers. If in doubt, leave off. Requires 'check_referrer' to be enabled.",
); // TODO

$this->config['check_proxy'] = array(
	'value' => false,
	'type' => 'boolean',
	'description' => 'Whether or not to check for proxies when posting.',
); // TODO

$this->config['allow_duplicate_images'] = array(
	'value' => false,
	'type' => 'boolean',
	'description' => 'Allow duplicate images when posting.',
); // TODO

$this->config['allow_duplicate_text'] = array(
	'value' => false,
	'type' => 'boolean',
	'description' => 'Whether or not to allow duplicate text posts.',
);

$this->config['seconds_between_posts'] = array(
	'value' => 5,
	'type' => 'integer',
	'description' => 'Minimum time in seconds between posts from the same IP.',
);

$this->config['seconds_between_images'] = array(
	'value' => 10,
	'type' => 'integer',
	'description' => 'Minimum time in seconds between image posts from the same IP.',
);

$this->config['seconds_between_duplicate_text'] = array(
	'value' => 900,
	'type' => 'integer',
	'description' => "Minimum time in seconds between identical text-only posts, regardless of IP. 'allow_duplicate_text' must be enabled for this to have any effect.",
);

$this->config['enable_reports'] = array(
	'value' => true,
	'type' => 'boolean',
	'description' => 'Whether or not post reporting should be enabled for this board.',
); // TODO
