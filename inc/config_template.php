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

# database driver - choose either mysql or sqlite
$db_driver = <?php var_export($vars['db_driver']) ?>;

# mysql: name of database
# sqlite: path to database file - make sure it's not accessible from the web
$db_name = <?php var_export($vars['db_name']) ?>;

# database username - mysql only
$db_username = <?php var_export($vars['db_username']) ?>;

# database password - mysql only
$db_password = <?php var_export($vars['db_password']) ?>;

# database host - usually 'localhost', but may vary
$db_host = <?php var_export($vars['db_username']) ?>;

# table prefix - if you're sharing the database with another application (i.e.
# wordpress or another PlainIB install), then set this to something unique.
$db_prefix = <?php var_export($vars['db_prefix']) ?>;


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
