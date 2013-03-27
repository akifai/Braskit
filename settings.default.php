<?php
defined('TINYIB') or exit;

define('TINYIB_BOARDDESC', "TinyIB"); // Displayed at the top of every page
define('TINYIB_MAXTHREADS', 100); // Oldest threads are discarded over this limit  [0 to disable]
define('TINYIB_TRUNCATE', 15); // Messages are truncated to this many lines on board index pages  [0 to disable]
define('TINYIB_MAXKB', 2048); // Maximum file size in kilobytes  [0 to disable]
define('TINYIB_MAXKBDESC', "2 MB"); // Human-readable representation of the maximum file size
define('TINYIB_MAXW', 200); // Maximum image width
define('TINYIB_MAXH', 200); // Maximum image height - Images exceeding these sizes will be thumbnailed
define('TINYIB_DELAY', 30); // Delay between posts to help control flooding  [0 to disable]
define('TINYIB_TRIPSEED', ""); // Enter some random text - Used when generating secure tripcodes - Must not change once set
define('TINYIB_ADMINPASS', ""); // Text entered at the manage prompt to gain administrator access
define('TINYIB_DBMODE', "mysql"); // Choose: mysql / sqlite
define('TINYIB_DEBUG', true);

// Note: The following only apply when TINYIB_DBMODE is set to mysql
define('TINYIB_DBHOST', "localhost");
define('TINYIB_DBUSERNAME', "");
define('TINYIB_DBPASSWORD', "");
define('TINYIB_DBNAME', "");
define('TINYIB_DBBANS', "bans");

// Remove this line after editing your config
throw new Exception("Please edit 'settings.php'.");
