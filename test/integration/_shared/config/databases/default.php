<?php

$database = [
    'adapter' => 'mysql',
    'host' => 'localhost',
    'port' => 3306,
    'username' => 'root',
    'password' => '',
    'dbname' => 'freischutz_example',
    'charset' => 'utf8',
    'options' => [
        PDO::ATTR_EMULATE_PREPARES => false,         // Use native prepared statements
        PDO::ATTR_STRINGIFY_FETCHES => false,        // Keep data types from database
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Throw exception on PDO error
    ],
];

return $database;
