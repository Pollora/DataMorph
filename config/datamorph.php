<?php
return [
    'pipelines' => [
        'stock' => [
            'hooks' => [
                'before_extract' => [
                    App\ETL\Stock\Hooks\BeforeStockExtract::class,
                ],
                'after_extract' => [
                    // Hooks to execute after extraction
                ],
                'before_transform' => [
                    // Hooks to execute before transformation
                ],
                'after_transform' => [
                    // Hooks to execute after transformation
                ],
                'before_load' => [
                    // Hooks to execute before loading
                ],
                'after_load' => [
                    // Hooks to execute after loading
                ],
                'before_run' => [
                    App\ETL\Stock\Hooks\BeforeStockRun::class,
                ],
                'after_run' => [
                    // Hooks to execute after complete execution
                ],
            ],
        ],
        // Other pipelines...
    ],
    'paths' => [
        'etl' => app_path('ETL'),
    ],
];