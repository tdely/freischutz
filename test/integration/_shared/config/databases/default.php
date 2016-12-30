<?php

$database = [
    'adapter' => 'mysql',
    'host' => 'mysql',
    'port' => 3306,
    'username' => 'root',
    'password' => 'mysql',
    'dbname' => 'freischutz_test',
    'charset' => 'utf8',
    'options' => [
        PDO::ATTR_EMULATE_PREPARES => false,         // Use native prepared statements
        PDO::ATTR_STRINGIFY_FETCHES => false,        // Keep data types from database
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Throw exception on PDO error
    ],
];

return $database;
