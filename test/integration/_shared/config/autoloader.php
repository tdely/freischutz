<?php

use Phalcon\Loader as PhalconLoader;

$loader = new PhalconLoader();

$loader->registerNamespaces(array(
    'Freischutz' => $libDir,
    'Freischutz\Application' => $libDir . '/application',
    'Freischutz\Security' => $libDir . '/security',
    'Freischutz\Utility' => $libDir . '/utility',
    'Test\Controllers' => $appDir . '/controllers',
    'Test\Models' => $appDir . '/models',
));

$loader->register();
