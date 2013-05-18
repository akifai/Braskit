<?php
defined('TINYIB') or exit;

/*
 * This file contains the default config for boards.
 *
 * Don't edit it - these settings can be changed from the moderator panel.
 */

$site_name = 'PlainIB Imageboard';

$home_url = '/';

$allow_thread_textonly = false; // TODO
$allow_thread_images = true; // TODO
$allow_reply_textonly = true; // TODO
$allow_reply_image = true; // TODO

$max_thumb_w = 200;
$max_thumb_h = 200;

$threads_per_page = 10; // TODO
$replies_shown = 5; // TODO

$max_threads = 100;

$max_kb = 3000;

$default_name = "Anonymous";
$default_subject = "";
$default_comment = "";

$forced_anon = false; // TODO
$allow_tripcodes = true; // TODO
$allow_email = false; // TODO
$allow_sage = true; // TODO
$auto_noko = true; // TODO

$display_id = false; // TODO

$spam_trap = true; // TODO
$check_spam = true; // TODO

$check_referrer = true; // TODO
$check_referrer_strict = false; // TODO

$check_proxy = false; // TODO

$allow_duplicate_images = false; // TODO
$allow_duplicate_text = false;

$seconds_between_posts = 5;
$seconds_between_images = 10;
$seconds_between_duplicate_text = 900;

$enable_reports = true; // TODO
