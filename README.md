# PlainIB

Lightweight [imageboard](http://en.wikipedia.org/wiki/Imageboard) script written
in PHP, based on [TinyIB](https://github.com/tslocum/TinyIB/). The script has
been largely rewritten and hardly resembles TinyIB now.

**Do not use this software. It is still in early beta and is not yet ready for
production use. Contributors are welcome.**

## Requirements

* Some webserver
* PHP >= 5.3
* PostgreSQL >= 9.1
* GD (library) or Imagemagick (CLI/library)

## Project goal

Create a scalable, extendable and easily configurable imageboard script which is
capable of powering multiple high-traffic imageboard sites on a single server.

## Contributing

To contribute, fork this project on GitHub, make the changes and send me a pull
request with details about your changes.

## Licence/copyrights

For simplicity's sake, all of PlainIB is licenced under the GNU GPL version 3.
This was Trevor's choice, not mine. In practice, this won't prevent you from
doing anything with the script except redistributing binaries of it without
distributing the source code.

Additionally, the software includes the following libraries:

* [Bootstrap](http://twitter.github.io/bootstrap/)
* [jQuery](http://jquery.com/); https://jquery.org/license/
* [JSMin+](http://crisp.tweakblogs.net/blog/cat/716)
* [lessphp](http://leafo.net/lessphp/)
* [Twig](http://twig.sensiolabs.org/)
