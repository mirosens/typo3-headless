<?php

namespace Vendor\FahnCore\Hooks;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;

/**
 * TypoScriptHook
 * 
 * Bindet automatisch die TypoScript-Konfiguration der fahn_core Extension
 * in Root-Templates ein, wenn sie noch nicht vorhanden ist.
 * 
 * @package Vendor\FahnCore\Hooks
 */
final class TypoScriptHook
{
    private const TYPOSCRIPT_CONSTANTS = "@import 'EXT:fahn_core/Configuration/TypoScript/constants.typoscript'";
    private const TYPOSCRIPT_SETUP = "@import 'EXT:fahn_core/Configuration/TypoScript/setup.typoscript'";

    /**
     * Wird nach dem Speichern von Template-Datensätzen aufgerufen
     * Bindet automatisch die TypoScript-Konfiguration in Root-Templates ein
     */
    public function processDatamap_afterDatabaseOperations(
        string $status,
        string $table,
        string $uid,
        array $fields,
        DataHandler $dataHandler
    ): void {
        // Nur für sys_template-Tabellen relevant
        if ($table !== 'sys_template') {
            return;
        }

        // Prüfe, ob es sich um ein Root-Template handelt
        if (!$this->isRootTemplate((int)$uid)) {
            return;
        }

        // Hole den Template-Datensatz
        $templateRecord = $this->getTemplateRecord((int)$uid);
        if (!$templateRecord) {
            return;
        }

        // Prüfe, ob die TypoScript-Dateien bereits eingebunden sind
        $constants = $templateRecord['constants'] ?? '';
        $setup = $templateRecord['setup'] ?? '';
        $includeStaticFiles = $templateRecord['include_static_file'] ?? '';

        $needsUpdate = false;
        $updatedConstants = $constants;
        $updatedSetup = $setup;
        $updatedIncludes = $includeStaticFiles;

        // Prüfe Constants
        if (!str_contains($constants, self::TYPOSCRIPT_CONSTANTS) 
            && !str_contains($includeStaticFiles, 'fahn_core/Configuration/TypoScript')) {
            if (empty($updatedConstants)) {
                $updatedConstants = self::TYPOSCRIPT_CONSTANTS . "\n";
            } else {
                $updatedConstants = self::TYPOSCRIPT_CONSTANTS . "\n" . $updatedConstants;
            }
            $needsUpdate = true;
        }

        // Prüfe Setup
        if (!str_contains($setup, self::TYPOSCRIPT_SETUP) 
            && !str_contains($includeStaticFiles, 'fahn_core/Configuration/TypoScript')) {
            if (empty($updatedSetup)) {
                $updatedSetup = self::TYPOSCRIPT_SETUP . "\n";
            } else {
                $updatedSetup = self::TYPOSCRIPT_SETUP . "\n" . $updatedSetup;
            }
            $needsUpdate = true;
        }

        // Oder prüfe, ob statische Datei bereits eingebunden ist
        if (!str_contains($includeStaticFiles, 'fahn_core/Configuration/TypoScript')) {
            // Versuche statische Datei hinzuzufügen (wenn möglich)
            // Dies ist die bevorzugte Methode in TYPO3
        }

        // Aktualisiere den Datensatz, wenn nötig
        if ($needsUpdate) {
            $this->updateTemplateRecord((int)$uid, $updatedConstants, $updatedSetup);
        }
    }

    /**
     * Prüft, ob ein Template ein Root-Template ist
     */
    private function isRootTemplate(int $uid): bool
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $queryBuilder = $connectionPool->getQueryBuilderForTable('sys_template');
        
        $result = $queryBuilder
            ->select('pid', 'root')
            ->from('sys_template')
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)))
            ->executeQuery()
            ->fetchAssociative();

        if (!$result) {
            return false;
        }

        // Ein Root-Template hat root=1 oder ist auf der Root-Seite (pid=0 oder root page)
        return (bool)($result['root'] ?? false) || $this->isRootPage((int)$result['pid']);
    }

    /**
     * Prüft, ob eine Seite eine Root-Seite ist
     */
    private function isRootPage(int $pageId): bool
    {
        if ($pageId === 0) {
            return true;
        }

        try {
            $rootline = GeneralUtility::makeInstance(RootlineUtility::class, $pageId);
            $rootlineArray = $rootline->get();
            return count($rootlineArray) === 1;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Holt einen Template-Datensatz
     */
    private function getTemplateRecord(int $uid): ?array
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $queryBuilder = $connectionPool->getQueryBuilderForTable('sys_template');
        
        $result = $queryBuilder
            ->select('*')
            ->from('sys_template')
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)))
            ->executeQuery()
            ->fetchAssociative();

        return $result ?: null;
    }

    /**
     * Aktualisiert einen Template-Datensatz
     */
    private function updateTemplateRecord(int $uid, string $constants, string $setup): void
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $queryBuilder = $connectionPool->getQueryBuilderForTable('sys_template');
        
        $queryBuilder
            ->update('sys_template')
            ->set('constants', $constants)
            ->set('setup', $setup)
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)))
            ->executeStatement();
    }

    /**
     * Alternative Methode: Bindet TypoScript beim Frontend-Rendering ein
     * Wird von ext_localconf.php aufgerufen (falls der Hook dort registriert ist)
     */
    public function includeTypoScript(): void
    {
        // Diese Methode kann verwendet werden, um TypoScript dynamisch einzubinden
        // Sie wird jedoch nicht automatisch aufgerufen, da TYPO3 v13 keine
        // tslib/class.tslib_fe.php Hooks mehr unterstützt
    }
}

