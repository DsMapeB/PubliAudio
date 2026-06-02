<?php

/**
 * Configuración PDO para la base de datos spotyaudio.
 *
 * Lee las variables del archivo .env con valores por defecto
 * compatibles con Laragon (MySQL/MariaDB local).
 *
 * @return array Configuración de conexión PDO
 */
return [

    'driver'    => env('DB_CONNECTION', 'mysql'),
    'host'      => env('DB_HOST', 'localhost'),
    'port'      => env('DB_PORT', '3306'),
    'database'  => env('DB_DATABASE', 'spotyaudio'),
    'username'  => env('DB_USERNAME', 'root'),
    'password'  => env('DB_PASSWORD', ''),

    'charset'   => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix'    => '',

    'options' => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_PERSISTENT         => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
    ],

];
