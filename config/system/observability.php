<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Log\Writer\FileWriter;
use TYPO3\CMS\Core\Log\Processor\WebProcessor;
use TYPO3\CMS\Core\Log\Processor\IntrospectionProcessor;
use TYPO3\CMS\Core\Log\Processor\MemoryUsageProcessor;

defined('TYPO3') or die();

// Env-basiertes Log-Level
$envLogLevel = getenv('TYPO3_LOG_LEVEL') ?: 'INFO';
$minLogLevel = LogLevel::INFO;

switch (strtoupper($envLogLevel)) {
    case 'DEBUG':
        $minLogLevel = LogLevel::DEBUG;
        break;
    case 'WARNING':
        $minLogLevel = LogLevel::WARNING;
        break;
    case 'ERROR':
        $minLogLevel = LogLevel::ERROR;
        break;
}

// Writer-Konfiguration: stdout/stderr
$GLOBALS['TYPO3_CONF_VARS']['LOG'] = array_replace_recursive(
    $GLOBALS['TYPO3_CONF_VARS']['LOG'] ?? [],
    [
        'TYPO3' => [
            'writerConfiguration' => [
                LogLevel::INFO => [
                    FileWriter::class => [
                        'stream' => 'php://stdout',
                        'logFormat' => '%date% %level% %component% %message% %data%',
                    ],
                ],
                LogLevel::ERROR => [
                    FileWriter::class => [
                        'stream' => 'php://stderr',
                        'logFormat' => '%date% %level% %component% %message% %data%',
                    ],
                ],
            ],
        ],
    ]
);

// Prozessoren - gruppiert nach Log-Level
$GLOBALS['TYPO3_CONF_VARS']['LOG']['TYPO3']['processorConfiguration'] = array_replace_recursive(
    $GLOBALS['TYPO3_CONF_VARS']['LOG']['TYPO3']['processorConfiguration'] ?? [],
    [
        $minLogLevel => [
            WebProcessor::class => [
                'logLevel' => $minLogLevel,
            ],
            MemoryUsageProcessor::class => [
                'logLevel' => $minLogLevel,
            ],
            \Vendor\FahnCore\Log\Processor\RequestIdProcessor::class => [
                'logLevel' => $minLogLevel,
            ],
        ],
        LogLevel::ERROR => [
            IntrospectionProcessor::class => [
                'logLevel' => LogLevel::ERROR,
                'options' => [
                    'skipClassesPartials' => [__CLASS__],
                ],
            ],
        ],
    ]
);


