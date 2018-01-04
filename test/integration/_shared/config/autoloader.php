<?php

use Phalcon\Loader as PhalconLoader;

$loader = new PhalconLoader();

$loader->registerNamespaces(array(
    'Freischutz' => $libDir,
    'Test\Controllers' => $appDir . '/controllers',
    'Test\Models' => $appDir . '/models',
));

$loader->register();
