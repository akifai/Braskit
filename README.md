# PlainIB

Lightweight [image board](http://en.wikipedia.org/wiki/Imageboard) script
written in PHP, based on [TinyIB](https://github.com/tslocum/TinyIB/). The
script has been largely rewritten and hardly resembles TinyIB now.

**Do not use this software. It is still in a very early beta and is not yet
ready for production use. Contributors are welcome.**

## Differences between this and TinyIB

* Parameterised PDO everywhere. The same code is used for both MySQL and SQLite.
* No flatfile support, because it's slow and stupid.
* Templates with [Twig](http://twig.sensiolabs.org/).

## Project goal

Create a scalable and easily configurable imageboard script which is capable of
powering multiple high-traffic imageboard sites on a single server.

## Contributing

To contribute, fork this project on GitHub, make the changes and send me a pull
request with details about your changes.

Contributions will be accepted if:

* The code doesn't suck. Read a few `inc/task.*.php` files and try to follow the
  same code style. Tabs are 8 spaces wide, lines *should* be no longer than 80
  characters and you *should not* use more than three indentation levels.
* The code is compatible with PHP 5.2.4 and all later versions. It must also
  work on 32-bit and 64-bit machines alike and with both MySQL and SQLite.
* The code is useful. We don't want dice rollers/country balls or any of that
  crap.
* The code is fast. My dev environment is a Raspberry Pi with 256 MB RAM, nginx,
  PHP 5.4, PHP-FPM, APC and SQLite. If it takes 10 seconds to execute a piece of
  code on my rPi, then it is too slow.
* The code was written by you.

Also, by contributing, you agree that your contributions may be relicensed under
any licence of my choosing.

# Licence

For simplicity's sake, all of PlainIB is licenced under the GNU GPL version 3.
This was Trevor's choice, not mine. In effect, however, this won't prevent you
from doing anything except redistributing binaries without distributing the
source, and who the fuck does that with PHP scripts anyway?

The software includes Twig which is released under a permissive BSD licence. See
`inc/lib/Twig/LICENSE` for details.
