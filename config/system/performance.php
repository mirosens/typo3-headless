<?php

/**
 * FAHN-CORE Performance Layer Configuration (PHASE A.5)
 * 
 * Diese Datei wird von ext_localconf.php (fahn_core Extension) eingebunden und enthält
 * Redis-Cache-, Cache-Tag- und CDN-Konfiguration.
 * 
 * Diese Datei wird NICHT von DDEV überschrieben.
 */

use FahnCore\Config\ConfigurationBootstrapper;

$fcConfig = ConfigurationBootstrapper::build();

$redisHost   = $fcConfig['redis']['host'];
$redisPort   = $fcConfig['redis']['port'];
$cachePrefix = rtrim($fcConfig['redis']['prefix'], '_') . '_';

// ========================================
// REDIS CACHE BACKENDS
// ========================================

// pages-Cache
// Hinweis: Redis unterstützt standardmäßig nur 16 Datenbanken (0-15)
$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['pages']['backend'] =
    \TYPO3\CMS\Core\Cache\Backend\RedisBackend::class;
$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['pages']['options'] = [
    'hostname'    => $redisHost,
    'port'        => $redisPort,
    'database'    => 1,
    'compression' => true,
    'keyPrefix'   => $cachePrefix . 'pages:',
];

// rootline-Cache
$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['rootline']['backend'] =
    \TYPO3\CMS\Core\Cache\Backend\RedisBackend::class;
$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['rootline']['options'] = [
    'hostname'    => $redisHost,
    'port'        => $redisPort,
    'database'    => 2,
    'compression' => true,
    'keyPrefix'   => $cachePrefix . 'rootline:',
];

// hash-Cache (wird von vielen Extensions verwendet)
$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['hash']['backend'] =
    \TYPO3\CMS\Core\Cache\Backend\RedisBackend::class;
$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['hash']['options'] = [
    'hostname'    => $redisHost,
    'port'        => $redisPort,
    'database'    => 0, // Standard-Datenbank
    'compression' => true,
    'keyPrefix'   => $cachePrefix . 'hash:',
];

// Optional: eigener API-Cache (z. B. für vorberechnete JSON-Responses)
// Hinweis: Redis unterstützt standardmäßig nur 16 Datenbanken (0-15)
$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['fahn_core_api'] = [
    'backend' => \TYPO3\CMS\Core\Cache\Backend\RedisBackend::class,
    'options' => [
        'hostname'    => $redisHost,
        'port'        => $redisPort,
        'database'    => 3, // Geändert von 20 auf 3 (innerhalb des Standardbereichs 0-15)
        'compression' => true,
        'keyPrefix'   => $cachePrefix . 'api:',
    ],
];

// Optional: FE-Sessions in Redis (Non-Sticky-Sessions)
// Hinweis: Redis unterstützt standardmäßig nur 16 Datenbanken (0-15)
$GLOBALS['TYPO3_CONF_VARS']['SYS']['session']['FE']['backend'] =
    \TYPO3\CMS\Core\Session\Backend\RedisSessionBackend::class;
$GLOBALS['TYPO3_CONF_VARS']['SYS']['session']['FE']['options'] = [
    'hostname'    => $redisHost,
    'port'        => $redisPort,
    'database'    => 4, // Geändert von 11 auf 4 (innerhalb des Standardbereichs 0-15)
    'keyPrefix'   => $cachePrefix . 'fe_sess:',
    'compression' => false,
];

// ========================================
// CACHE-TAG-KONFIGURATION
// ========================================
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fahn_core']['performance']['cacheTags'] = $fcConfig['cacheTags'];

// ========================================
// CDN / STORAGE PROXY
// ========================================
$GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['headless.storageProxy'] =
    $fcConfig['cdn']['storageProxyEnabled'];

