<?php

$database = [
    'adapter' => 'mysql',
    'host' => '127.0.0.1',
    'port' => 3306,
    'username' => 'excession',
    'password' => 'excession',
    'dbname' => 'excession',
    'charset' => 'utf8mb4',
    'options' => [
        PDO::ATTR_EMULATE_PREPARES => false,         // Use native prepared statements
        PDO::ATTR_STRINGIFY_FETCHES => false,        // Keep data types from database
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Throw exception on PDO error
    ],
];

return $database;
