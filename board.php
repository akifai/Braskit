<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

require __DIR__.'/vendor/autoload.php';

$app = new App();

$app['controller'] = new Controller_Web($app);

$app->run();
