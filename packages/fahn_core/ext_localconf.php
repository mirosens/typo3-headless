<?php

defined('TYPO3') or die();

/**
 * FAHN-CORE Extension Local Configuration
 * 
 * Lädt projektweite Konfigurationsdateien (Performance, Observability)
 * die nicht von DDEV überschrieben werden.
 */

// Projekt-Root bestimmen (2 Ebenen hoch vom Extension-Verzeichnis)
$projectRoot = dirname(dirname(__DIR__));
$configDir = $projectRoot . '/config/system';

// Lade Performance-Konfiguration (PHASE A.5)
$performanceConfigFile = $configDir . '/performance.php';
if (file_exists($performanceConfigFile)) {
    try {
        require $performanceConfigFile;
    } catch (\Throwable $e) {
        // Silently fail if classes are not yet available
    }
}

// Lade Observability-Konfiguration (PHASE A.10)
// Wird nur geladen, wenn TYPO3 Core-Klassen verfügbar sind
if (class_exists(\TYPO3\CMS\Core\Log\LogLevel::class)) {
    $observabilityConfigFile = $configDir . '/observability.php';
    if (file_exists($observabilityConfigFile)) {
        try {
            require $observabilityConfigFile;
        } catch (\Throwable $e) {
            // Silently fail if there's an error
        }
    }
}

// Plugin-Registrierung für Login-Controller (Phase C2)
call_user_func(function () {
    // Registrierung des Login‑Plugins
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
        'FahnCore',
        'Login',
        [\Fahn\Core\Controller\LoginController::class => 'login, session, logout'],
        [\Fahn\Core\Controller\LoginController::class => 'login, session, logout']
    );

    // Cache‑Registrierung für Rate Limiting (Login‑Versuche)
    if (!isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['fahn_core_login'])) {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['fahn_core_login'] = [
            'backend' => \TYPO3\CMS\Core\Cache\Backend\SimpleFileBackend::class,
            'options' => [],
        ];
    }

    // Automatische TypoScript-Einbindung für Root-Templates (Phase C3)
    // Dies stellt sicher, dass die API-Konfiguration immer geladen wird
    // Registriere DataHandler-Hook für automatische Einbindung beim Speichern von Templates
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = 
        \Vendor\FahnCore\Hooks\TypoScriptHook::class;

    // cHash-Ausnahmen für API-Parameter (Phase 2.5)
    // Verhindert "cHash empty" Fehler bei API-Requests
    if (!isset($GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParameters'])) {
        $GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParameters'] = [];
    }
    $excludedParams = [
        'tx_fahncore_login[action]',
        'tx_fahncorefahndung_api[action]',
        'tx_fahncore_login',
        'tx_fahncorefahndung_api',
    ];
    $GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParameters'] = array_merge(
        $GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParameters'],
        $excludedParams
    );
});
