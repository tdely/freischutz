<?php

use Phalcon\Loader as PhalconLoader;

$loader = new PhalconLoader();

$loader->registerNamespaces(array(
    'Freischutz' => LIB_DIR,
    'Reference\Controllers' => APP_DIR . '/controllers',
    'Reference\Models' => APP_DIR . '/models',
));

$loader->register();
