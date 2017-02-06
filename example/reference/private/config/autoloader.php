<?php

use Phalcon\Loader as PhalconLoader;

$loader = new PhalconLoader();

$loader->registerNamespaces(array(
    'Freischutz' => LIB_DIR,
    'Freischutz\Application' => LIB_DIR . '/application',
    'Freischutz\Security' => LIB_DIR . '/security',
    'Freischutz\Utility' => LIB_DIR . '/utility',
    'Reference\Controllers' => APP_DIR . '/controllers',
    'Reference\Models' => APP_DIR . '/models',
));

$loader->register();