# Braskit

Braskit is an imageboard script written in PHP with a PostgreSQL storage
backend. It attempts to do right where other imageboards fail.

**This software is in an early development phase and is not suitable for running
on live, public sites yet.**

## Requirements

* Some web server
* PHP >= 5.3.7
* PostgreSQL >= 9.2
* [PHP GD][php-gd] or [Imagemagick][imagemagick]'s `convert` utility for
  thumbnailing

Debian 6 includes PHP 5.3.3 which does not meet the requirements. You can either
use [Dotdeb][dotdeb] repositories or suck it up and upgrade to Wheezy.

Both Debian 6 & 7 fail to meet the version requirement for PostgreSQL. Luckily,
[official PostgreSQL packages][debian-postgres] for Debian are available.

And no, MySQL will not work. Period. Don't even try.

## Contributing

To contribute, fork this project on GitHub, make the changes and send me a pull
request with details about your changes.

## Licence/copyrights

Braskit is open source. It is licensed under the GNU GPL version 3. See the
included LICENSE file for details.

[php-gd]: http://www.php.net/manual/en/book.image.php
[imagemagick]: http://www.imagemagick.org/
[dotdeb]: http://www.dotdeb.org/
[debian-postgres]: http://www.postgresql.org/download/linux/debian/
