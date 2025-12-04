<?php

use Vendor\FahnCore\Middleware\HealthCheckMiddleware;

return [
    'frontend' => [
        'vendor/fahn-core/health-check' => [
            'target' => HealthCheckMiddleware::class,
            'before' => [
                'typo3/cms-frontend/site',
            ],
        ],
        'fahn-core/cors' => [
            'target' => \Vendor\FahnCore\Middleware\CorsMiddleware::class,
            'before' => [
                'typo3/cms-frontend/tsfe',
            ],
        ],
        'fahn-core/cache-tags' => [
            'target' => \Vendor\FahnCore\Middleware\CacheTagHeaderMiddleware::class,
            'after'  => ['typo3/cms-frontend/tsfe'],
        ],
        'fahn-core/json-404' => [
            'target' => \Vendor\FahnCore\Middleware\Json404Middleware::class,
            'after'  => ['typo3/cms-frontend/tsfe'],
        ],
    ],
];


