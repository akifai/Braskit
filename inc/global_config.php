<?php
defined('TINYIB') or exit;

/*
 * This file contains the default config for boards.
 *
 * Don't edit it - these settings can be changed from the moderator panel.
 */

$site_name = 'PlainIB Imageboard';

$allow_thread_textonly = false;
$allow_thread_images = true;
$allow_reply_textonly = true;
$allow_reply_image = true;

$max_thumb_w = 200;
$max_thumb_h = 200;

$threads_per_page = 10;
$replies_shown = 5;

$max_threads = 100;

$max_kb = 3000;

$default_name = "Anonymous";
$default_subject = "";
$default_comment = "";

$forced_anon = false;
$allow_tripcodes = true;
$allow_email = false;
$allow_sage = true;
$auto_noko = true;

$display_id = false;

$spam_trap = true;
$check_spam = true;

$check_referrer = true;
$check_referrer_strict = false;

$check_proxy = false;

$allow_duplicate_images = false;
$allow_duplicate_text = false;

$seconds_between_posts = 5;
$seconds_between_images = 10;
$seconds_between_duplicate_text = 900;

$enable_reports = true;
