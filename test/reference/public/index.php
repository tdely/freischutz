<?php

use Phalcon\Config\Adapter\Ini as Config;

// Define important directories
define('APP_DIR', __DIR__ . '/../private');
define('LIB_DIR', __DIR__ . '/../../../lib');

// Set up syslog
openlog('freischutz_test', LOG_CONS | LOG_NDELAY | LOG_PID, LOG_USER);

register_shutdown_function(function () {
    $error = error_get_last();
    if ($error['type'] === E_ERROR) {
        syslog(LOG_ERR,
            'Error: ' . $error['message'] . ' in ' . $error['file'] .
            ' on line ' . $error['line']
        );
        http_response_code(500);
        echo "An unexpected problem occurred.";
    }
    closelog();
});

try {
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
} catch (\Exception $e) {
    // Log errors
    syslog(LOG_ERR,
        'Fatal exception: ' . $e->getMessage() . ' in ' . $e->getFile() .
        ' on line ' . $e->getLine() . "\nStack trace:\n" .
        $e->getTraceAsString()
    );
    closelog();

    // Respond with a generic error message
    http_response_code(500);
    echo 'An unexpected problem occurred.';
}
