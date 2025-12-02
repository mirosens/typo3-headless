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

