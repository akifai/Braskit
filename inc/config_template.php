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
# Tweaks
#

# temporary directory - you should probably not change this
$temp_dir = sys_get_temp_dir();

# enable debug mode - this will slow the board down considerably, but help with
# debugging and development
$debug = false;
<?php
	$output = ob_get_contents();
	ob_end_clean();

	return $output;
}
