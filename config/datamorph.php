<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuration par défaut
    |--------------------------------------------------------------------------
    */
    'default_source' => 'csv',
    'default_loader' => 'database',

    /*
    |--------------------------------------------------------------------------
    | Configuration du logging
    |--------------------------------------------------------------------------
    */
    'logging' => [
        'channel' => 'datamorph',
        'level' => 'info',
        'separate_errors' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuration du batch processing
    |--------------------------------------------------------------------------
    */
    'batch_size' => env('DATAMORPH_BATCH_SIZE', 500),
    'async' => [
        'enabled' => env('DATAMORPH_ASYNC_ENABLED', false),
        'queue' => env('DATAMORPH_QUEUE', 'default'),
        'connection' => env('DATAMORPH_QUEUE_CONNECTION', 'redis'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuration des sources de données
    |--------------------------------------------------------------------------
    */
    'sources' => [
        'csv' => [
            'path' => storage_path('app/imports'),
            'delimiter' => ',',
            'enclosure' => '"',
            'escape' => '\\',
            'headers' => true,
        ],
        'database' => [
            'connection' => env('DB_CONNECTION', 'mysql'),
            'table' => null,
        ],
        'api' => [
            'base_url' => null,
            'headers' => [],
            'timeout' => 30,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuration des destinations
    |--------------------------------------------------------------------------
    */
    'loaders' => [
        'database' => [
            'connection' => env('DB_CONNECTION', 'mysql'),
            'table' => null,
            'chunk_size' => 1000,
        ],
        'csv' => [
            'path' => storage_path('app/exports'),
            'delimiter' => ',',
            'enclosure' => '"',
            'escape' => '\\',
            'include_headers' => true,
        ],
        'api' => [
            'endpoint' => null,
            'method' => 'POST',
            'headers' => [],
            'batch_size' => 100,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuration des transformations
    |--------------------------------------------------------------------------
    */
    'transformers' => [
        'default' => [
            'class' => \Pollora\Datamorph\Transformers\DefaultTransformer::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuration de la queue
    |--------------------------------------------------------------------------
    */
    'queue' => [
        'enabled' => false,
        'connection' => env('QUEUE_CONNECTION', 'sync'),
        'queue' => 'default',
    ],
]; 