<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the database connections below you wish
    | to use as your default connection for database operations. This is
    | the connection which will be utilized unless another connection
    | is explicitly specified when you execute a query / statement.
    |
    */

    'default' => env('DB_CONNECTION', 'sqlite'),

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Below are all of the database connections defined for your application.
    | An example configuration is provided for each database system which
    | is supported by Laravel. You're free to add / remove connections.
    |
    */

    'connections' => [

        // ── Database App (default) ─────────────────────────────
        'mysql' => [
            'driver'         => 'mysql',
            'host'           => env('DB_HOST', 'localhost'),
            'port'           => env('DB_PORT', '3306'),
            'database'       => env('DB_DATABASE', 'db_coal_app'),
            'username'       => env('DB_USERNAME', 'root'),
            'password'       => env('DB_PASSWORD', ''),
            'unix_socket'    => env('DB_SOCKET', ''),
            'charset'        => 'utf8mb4',
            'collation'      => 'utf8mb4_unicode_ci',
            'prefix'         => '',
            'prefix_indexes' => true,
            'strict'         => true,
            'engine'         => null,
        ],

        // ── Database Timbangan CY ──────────────────────────────
        'mysql_cy' => [
            'driver'         => 'mysql',
            'host'           => env('DB_CY_HOST', '10.9.4.15'),
            'port'           => env('DB_CY_PORT', '3306'),
            'database'       => env('DB_CY_DATABASE', 'db_wb_sumsel_prod'),
            'username'       => env('DB_CY_USERNAME', 'it_dev'),
            'password'       => env('DB_CY_PASSWORD', ''),
            'unix_socket'    => '',
            'charset'        => 'latin1',
            'collation'      => 'latin1_swedish_ci',
            'prefix'         => '',
            'prefix_indexes' => true,
            'strict'         => false,
            'engine'         => null,
        ],

        // ── Database WBS ───────────────────────────────────────
        'mysql_wbs' => [
            'driver'         => 'mysql',
            'host'           => env('DB_WBS_HOST', '192.168.25.12'),
            'port'           => env('DB_WBS_PORT', '3306'),
            'database'       => env('DB_WBS_DATABASE', 'dbwbtjbaru'),
            'username'       => env('DB_WBS_USERNAME', 'IT'),
            'password'       => env('DB_WBS_PASSWORD', ''),
            'unix_socket'    => '',
            'charset'        => 'latin1',
            'collation'      => 'latin1_swedish_ci',
            'prefix'         => '',
            'prefix_indexes' => true,
            'strict'         => false,
            'engine'         => null,
            'options'   => [
        \PDO::ATTR_TIMEOUT => 3, // ← gagal dalam 3 detik, tidak nunggu lama
    ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    |
    | This table keeps track of all the migrations that have already run for
    | your application. Using this information, we can determine which of
    | the migrations on disk haven't actually been run on the database.
    |
    */

    'migrations' => [
        'table' => 'migrations',
        'update_date_on_publish' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    |
    | Redis is an open source, fast, and advanced key-value store that also
    | provides a richer body of commands than a typical key-value system
    | such as Memcached. You may define your connection settings here.
    |
    */

    'redis' => [

        'client' => env('REDIS_CLIENT', 'phpredis'),

        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_database_'),
            'persistent' => env('REDIS_PERSISTENT', false),
        ],

        'default' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
        ],

        'cache' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '1'),
        ],

    ],

];
