<?php

use Phalcon\Config\Adapter\Ini as Config;

// Define important directories
define('APP_DIR', __DIR__ . '/../private');
define('LIB_DIR', __DIR__ . '/../../../lib');

register_shutdown_function(function () {
    /*
     * If a fatal error occurs, fail gracefully.
     */
    $error = error_get_last();
    if ($error['type'] === E_ERROR) {
        http_response_code(500);
        echo "An unexpected problem occurred.";
    }
});

// Load config
$config = new Config(APP_DIR . '/config/config.ini');

// Add application directory path to config
$config->application->offsetSet('app_dir', APP_DIR);

// Load auto-loader
require APP_DIR . '/config/autoloader.php';

// Set up application
$app = new Freischutz\Application\Core($config);

// Execute
$app->run();
