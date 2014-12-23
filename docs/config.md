# Configuration subsystem

I'm too lazy to write the documentation for the configuration subsystem (i.e.
the classes in the `Braskit\Config` namespace) now. However, I'd just like to
give a demonstration of how config pools work:

    $pool = $app['config']->getPool('board.%', ['kittens']);
    $pool->get('forced_anon'); // returns true

    // different board name
    $pool = $app['config']->getPool('board.%', ['puppies']);
    $pool->get('forced_anon'); // returns false

    // invalid key - "allow_new_threads" clearly has nothing to do with the
    // configuration for an existing thread 
    $pool = $app['config']->getPool('board.%.thread.%', ['b', 12345]);
    $pool->get('allow_new_threads'); // results in an error

## TL;DR

* The pool identifier (e.g. `board.%`) is associated with a dictionary which
  defines all the valid keys for that identifier.
* The second argument is an array which takes the same number of elements ("pool
  args") as the number of percent signs in the pool identifier.
* Varying pool args is practically a way of namespacing sets of key/value pairs
  sharing the same pool identifier.
* Sticking to the naming scheme for pool identifiers and keys in the above
  example is a good idea. You could name it `board.thread.%.%` or `thread%%`,
  but the idea is that the percent signs get substituted for pool args when the
  identifiers are "pretty printed".

BTW, don't even think about giving each thread its own configuration. It is just
an example. Doing so will kill performance.
