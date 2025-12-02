<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'FAHN-CORE Site Package',
    'description' => 'Site package for FAHN-CORE TYPO3 Headless project',
    'category' => 'templates',
    'author' => 'FAHN-CORE',
    'author_email' => '',
    'state' => 'stable',
    'clearCacheOnLoad' => 1,
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.0-13.4.99',
            'headless' => '4.0.0-4.99.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];








