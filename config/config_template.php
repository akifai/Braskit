<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

function create_config($vars) {
	ob_start();

	echo "<?php\n";
?>

# protects against outsiders - don't remove
isset($app) or exit;


#
# General
#

# cryptographic secret - DO NOT LOSE OR EDIT THIS KEY. Logins will break and
# secure tripcodes will be different!
$app['secret'] = <?php var_export($vars['secret']) ?>;

# A unique identifier for this install. It is used to avoid conflicts with
# other software.
$app['unique'] = <?php var_export($vars['unique']) ?>;


#
# Database
#

# name of database
$app['db.name'] = <?php var_export($vars['db_name']) ?>;

# database username
$app['db.username'] = <?php var_export($vars['db_username']) ?>;

# database password
$app['db.password'] = <?php var_export($vars['db_password']) ?>;

# database host - usually 'localhost', but may vary
$app['db.host'] = <?php var_export($vars['db_host']) ?>;

# table prefix - if you're sharing the database with another application (for
# instance another Braskit install), then set this to something unique.
$app['db.prefix'] = <?php var_export($vars['db_prefix']) ?>;

<?php
	$output = ob_get_contents();
	ob_end_clean();

	return $output;
}
