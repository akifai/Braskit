<?php
defined('TINYIB') or exit;

function create_config($vars) {
	ob_start();

	echo "<?php\n";
?>
defined('TINYIB') or exit;

#
# General
#

# cryptographic secret - DO NOT LOSE OR EDIT THIS KEY. Logins will break and
# secure tripcodes will be different!
$secret = <?php var_export($vars['secret']) ?>;

# name of database
$db_name = <?php var_export($vars['db_name']) ?>;

# database username
$db_username = <?php var_export($vars['db_username']) ?>;

# database password
$db_password = <?php var_export($vars['db_password']) ?>;

# database host - usually 'localhost', but may vary
$db_host = <?php var_export($vars['db_host']) ?>;

# table prefix - if you're sharing the database with another application (i.e.
# mediawiki or another PlainIB install), then set this to something unique.
$db_prefix = <?php var_export($vars['db_prefix']) ?>;


#
# File uploads
#

# What method is used to create thumbnails. Available methods are:
#   - convert (recommended, but requires ImageMagick's command line utility)
#   - gd (well-supported, but no transparency or animations)
#   - imagick (not recommended, requires ImageMagick's PHP extension)
#   - sips (OS X only, not recommended, sometimes fails whereas others don't)
$thumb_method = 'gd';


#
# Paths
#

# Available stylesheets as a name => path associative array.
$stylesheets = array(
	'Burichan' => 'burichan',
	'Futaba' => 'futaba',
	'Tomorrow' => 'tomorrow',
	'Yotsuba' => 'yotsuba',
	'Yotsuba B' => 'yotsuba-b',
);

# Default stylesheet
$default_stylesheet = 'Futaba';

# JavaScript files to include - these will be minified, merged into one file and
# included at the bottom of the page
$javascript_includes = array(
	'jquery-1.9.0.min.js',
	'bootstrap.min.js',
	'spin.js',
	'PlainIB.js',
);

# cache directory - you should probably not change this
$cache_dir = TINYIB_ROOT.'/cache';

# temporary directory - you should probably not change this
$temp_dir = sys_get_temp_dir();

# an array containing a list of files containing spam definitions. ideally,
# these files should be well hidden or inaccessible from the web.
$spam_files = array();

# uncomment to add spam.txt to the list
#$spam_files[] = 'spam.txt'; 


#
# Tweaks
#

# enable debug mode - this will slow the board down considerably, but help with
# debugging and development
$debug = false;

# Class to use for handling requests. ("RouteQueryString" / "RoutePathInfo")
# This will affect how dynamic URLs look like.
$request_handler = 'RouteQueryString';
<?php
	$output = ob_get_contents();
	ob_end_clean();

	return $output;
}
