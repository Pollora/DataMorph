<?php
return [
    'pipelines' => [
        'stock' => [
            'parameters' => [
                'extractor' => [
                    'connection' => 'mysql',
                ],
                'loader' => [
                    'path' => storage_path('app/output/woocommerce_stock.csv'),
                ],
            ],
        ],
    ],
    'etl_base_path' => app_path('ETL'),
];
