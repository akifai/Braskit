<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

/*
 * This file contains the default configuration for individual boards.
 *
 * Webmasters: don't edit this file - you can change the settings from the
 * moderator panel.
 */

$dict = new Braskit\Config\Dictionary();

$dict->add('allow_thread_textonly', [
	'default' => false,
	'type' => 'boolean',
]);

$dict->add('allow_thread_images', [
	'default' => true,
	'type' => 'boolean',
]); // TODO

$dict->add('allow_reply_textonly', [
	'default' => true,
	'type' => 'boolean',
]); // TODO

$dict->add('allow_reply_images', [
	'default' => true,
	'type' => 'boolean',
]); // TODO

$dict->add('max_thumb_w', [
	'default' => 200,
	'type' => 'number',
]);

$dict->add('max_thumb_h', [
	'default' => 200,
	'type' => 'number',
]);

$dict->add('threads_per_page', [
	'default' => 10,
	'type' => 'number',
]);

$dict->add('replies_shown', [
	'default' => 5,
	'type' => 'number',
]);

$dict->add('max_threads', [
	'default' => 100,
	'type' => 'number',
]);

$dict->add('max_kb', [
	'default' => 3000,
	'type' => 'number',
]);

$dict->add('default_name', [
	'default' => "Anonymous",
	'type' => 'text',
]);

$dict->add('default_subject', [
	'default' => "",
	'type' => 'text',
]);

$dict->add('default_comment', [
	'default' => "",
	'type' => 'textarea',
]);

$dict->add('forced_anon', [
	'default' => false,
	'type' => 'boolean',
]);

$dict->add('allow_tripcodes', [
	'default' => true,
	'type' => 'boolean',
]);

$dict->add('allow_email', [
	'default' => false,
	'type' => 'boolean',
]);

$dict->add('allow_sage', [
	'default' => true,
	'type' => 'boolean',
]);

$dict->add('auto_noko', [
	'default' => true,
	'type' => 'boolean',
]);

$dict->add('display_id', [
	'default' => false,
	'type' => 'boolean',
]); // TODO

$dict->add('enable_autobans', [
	'default' => true,
	'type' => 'boolean',
]);

$dict->add('autoban_seconds', [
	'default' => 21600, // 6 hours
	'type' => 'number',
]);

$dict->add('spam_trap', [
	'default' => true,
	'type' => 'boolean',
]); // TODO

$dict->add('autoban_trap_message', [
	'default' => 'Triggered a spam trap (auto-banned)',
	'type' => 'text',
]); // TODO

$dict->add('check_spam', [
	'default' => true,
	'type' => 'boolean',
]);

$dict->add('autoban_spam_message', [
	'default' => 'Auto-banned: "%s"',
	'type' => 'text',
]);

$dict->add('check_referrer', [
	'default' => true,
	'type' => 'boolean',
]); // TODO

$dict->add('check_referrer_strict', [
	'default' => false,
	'type' => 'boolean',
]); // TODO

$dict->add('check_proxy', [
	'default' => false,
	'type' => 'boolean',
]); // TODO

$dict->add('allow_duplicate_images', [
	'default' => false,
	'type' => 'boolean',
]); // TODO

$dict->add('allow_duplicate_text', [
	'default' => false,
	'type' => 'boolean',
]);

$dict->add('seconds_between_posts', [
	'default' => 5,
	'type' => 'number',
]);

$dict->add('seconds_between_images', [
	'default' => 10,
	'type' => 'number',
]);

$dict->add('seconds_between_duplicate_text', [
	'default' => 900,
	'type' => 'number',
]);

$dict->add('enable_reports', [
	'default' => true,
	'type' => 'boolean',
]);

$dict->add('seconds_between_reports', [
	'default' => 30,
	'type' => 'number',
]);

return $dict;
