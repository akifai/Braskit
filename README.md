# Braskit

Braskit is an imageboard script written in PHP with a PostgreSQL storage
backend. It attempts to do right where other imageboards fail.

**This software is in an early development phase and is not suitable for running
on live, public sites yet.**

## Requirements

* Some web server
* PHP 5.4 or higher
* PostgreSQL 9.2 or higher
* [PHP GD][php-gd] or [Imagemagick][imagemagick]'s `convert` utility for
  thumbnailing

Debian 7 fails to meet the version requirement for PostgreSQL. Luckily,
[official PostgreSQL packages][debian-postgres] for Debian are available.

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
