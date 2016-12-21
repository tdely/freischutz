<?php

use Phalcon\Loader as PhalconLoader;

$loader = new PhalconLoader();

$loader->registerNamespaces(array(
    'Freischutz' => LIB_DIR,
    'Freischutz\Application' => LIB_DIR . '/application',
    'Test\Controllers' => APP_DIR . '/controllers',
    'Test\Models' => APP_DIR . '/models',
));

$loader->register();
