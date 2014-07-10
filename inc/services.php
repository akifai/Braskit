<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

/*
 * Webmasters: do not edit this file. Instead, copy what you want to change to
 * config.php and edit it there instead.
 */

if (!(isset($app) && $app instanceof App)) {
    // protect against direct access
    exit();
}


//
// Default configuration
//

$app['cache.debug'] = false;

$app['cache.type'] = function () {
    if (ini_get('apc.enabled') && extension_loaded('apc')) {
        return 'apc';
    }

    return 'php';
};

$app['js.debug'] = false;

$app['js.includes'] = array(
    'vendor/jquery-2.1.1.min.js',
    'vendor/jquery.cookie.js',
    'vendor/spin.js',

    'braskit.js',
);

$app['less.debug'] = false;

$app['less.default_style'] = 'futaba';

$app['less.stylesheets'] = array(
    'burichan' => 'Burichan',
    'futaba' => 'Futaba',
    'tomorrow' => 'Tomorrow',
    'yotsuba' => 'Yotsuba',
    'yotsuba-b' => 'Yotsuba B',
);

$app['session.name'] = function () use ($app) {
    return 'SID_'.$app['unique'];
};

$app['template.debug'] = false;

$app['thumb.method'] = 'gd';

$app['thumb.quality'] = 75;

$app['thumb.convert_path'] = 'convert';

$app['timezone'] = 'UTC';

$app['unique'] = 'bs';


//
// Default paths
//

/*
* Terminology:
*
* - root
*     This is where the inc/ folder lies. Files in this category don't normally
*     get accessed from the web.
* - webroot
*     This is where files accessible through the web are stored.
* - entry
*     An entrypoint, e.g. board.php or ajax.php.
*/

$app['path.root'] = function () use ($app) {
    // should be set to the parent of the inc/ folder
    return realpath(dirname(__FILE__).'/..');
};

$app['path.webroot'] = function () use ($app) {
    return $app['path.root'];
};

$app['path.board'] = function () use ($app) {
    $webroot = $app['path.webroot'];
    return "$webroot/%s";
};

$app['path.boardtpl'] = function () use ($app) {
    $webroot = $app['path.webroot'];
    return "$webroot/%s/templates";
};

$app['path.boardpage'] = function () use ($app) {
    $webroot = $app['path.webroot'];
    return "$webroot/%s/%d.html";
};

$app['path.boardres'] = function () use ($app) {
    $webroot = $app['path.webroot'];
    return "$webroot/%s/res/%d.html";
};

$app['path.cache'] = function () use ($app) {
    $root = $app['path.root'];
    return "$root/cache/file";
};

$app['path.cache.tpl'] = function () use ($app) {
    $root = $app['path.root'];
    return "$root/cache/tpl";
};

$app['path.entry.ajax'] = function () use ($app) {
    $root = $app['path.root'];
    return "$root/ajax.php";
};

$app['path.entry.api'] = function () use ($app) {
    $root = $app['path.root'];
    return "$root/api.php";
};

$app['path.entry.board'] = function () use ($app) {
    $root = $app['path.root'];
    return "$root/board.php";
};

$app['path.entry.install'] = function () use ($app) {
    $root = $app['path.root'];
    return "$root/install.php";
};

$app['path.tpldir'] = function () use ($app) {
    $root = $app['path.root'];
    return "$root/inc/templates";
};

$app['path.tmp'] = function () {
    return sys_get_temp_dir();
};


//
// Default services
//

$app['cache'] = function () use ($app) {
    if ($app['cache.debug']) {
        return new Braskit\Cache_Debug();
    }

    switch ($app['cache.type']) {
    case 'apc':
        return new Braskit\Cache_APC();
    case 'php':
        return new Braskit\Cache_PHP($app['path.cache']);
    default:
        return new Braskit\Cache_Debug();
    }
};

$app['config'] = function () {
    return new GlobalConfig();
};

$app['counter'] = function () use ($app) {
    return new StdClass();
};

$app['csrf'] = function () use ($app) {
    return new Braskit\CSRF($app['param'], $app['session']);
};

$app['db'] = function () use ($app) {
    return new Braskit\Database($app['dbh'], $app['db.prefix']);
};

$app['dbh'] = function () use ($app) {
    return new Braskit\Database_Connection(
        $app['db.name'],
        $app['db.host'],
        $app['db.username'],
        $app['db.password'],
        $app['counter']
    );
};

$app['param'] = $app->factory(function () use ($app) {
    return new Braskit\Param($app['request']);
});

$app['request'] = function () use ($app) {
    return new Request();
};

$app['session'] = function () use ($app) {
    return new Session($app['session.name']);
};

$app['template'] = function () use ($app) {
    return $app['template.creator']($app['template.loader']);
};

$app['template.chain'] = $app->factory(function () {
    // returns a new chain loader
    return new Twig_Loader_Chain();
});

$app['template.loader'] = function () use ($app) {
    // returns a filesystem loader for inc/templates
    return new Braskit_Twig_Loader($app['path.tpldir']);
};

$app['thumb'] = function () use ($app) {
    $method = $app['thumb.method'];

    switch ($method) {
    case 'convert':
        return new Thumb_Convert($app['path.tmp'], array(
            'convert_path' => $app['thumb.convert_path'],
            'quality' => $app['thumb.quality'],
        ));
    case 'gd':
        return new Thumb_GD($app['path.tmp'], array(
            'quality' => $app['thumb.quality'],
        ));
    #case 'imagemagick':
    #case 'imagick':
    #    return new Thumb_Imagick($app['path.tmp']);
    case 'sips':
        return new Thumb_Sips($app['path.tmp']);
    }

    throw new LogicException("Unknown thumbnail method '$method'.");
};

$app['url'] = function () use ($app) {
    return new Braskit\UrlHandler_QueryString($app['request']);
};

$app['view'] = function () use ($app) {
    return new $app['router']->view($app);
};


//
// Misc
//

$app['template.creator'] = $app->protect(function ($loader) use ($app) {
    $twig = new Twig_Environment($loader, array(
        'cache' => $app['template.debug'] ? false : $app['path.cache.tpl'],
        'debug' => $app['template.debug'],
    ));

    $twig->addExtension(new Braskit_Twig_Extension());

    // Load debugger
    if ($app['template.debug']) {
        $twig->addExtension(new Twig_Extension_Debug());
    }

    return $twig;
});

/* vim: set ts=4 sw=4 sts=4 et: */
