<?php

declare(strict_types=1);

defined('TYPO3') or die();

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

// A.7.1.3: Neue Spalte tx_is_decorative für dekorative Bilder
$newSysFileReferenceColumns = [
    'tx_is_decorative' => [
        'exclude' => true,
        'label' => 'LLL:EXT:fahn_core/Resources/Private/Language/locallang_tca.xlf:sys_file_reference.tx_is_decorative',
        'config' => [
            'type' => 'check',
            'renderType' => 'checkboxToggle',
            'items' => [
                [
                    0 => 'LLL:EXT:fahn_core/Resources/Private/Language/locallang_tca.xlf:sys_file_reference.tx_is_decorative',
                    1 => '',
                ],
            ],
            'default' => 0,
        ],
    ],
];

// Spalte registrieren
ExtensionManagementUtility::addTCAcolumns('sys_file_reference', $newSysFileReferenceColumns);

// Positionierung nach "alternative"
ExtensionManagementUtility::addToAllTCAtypes(
    'sys_file_reference',
    'tx_is_decorative',
    '',
    'after:alternative'
);

// displayCond für das Feld "alternative": Nur anzeigen, wenn nicht dekorativ
$GLOBALS['TCA']['sys_file_reference']['columns']['alternative']['displayCond'] = 'FIELD:tx_is_decorative:REQ:false';

// A.7.2.1: Beschreibung für alternative-Feld hinzufügen
$GLOBALS['TCA']['sys_file_reference']['columns']['alternative']['description'] = 'LLL:EXT:fahn_core/Resources/Private/Language/locallang_tca.xlf:sys_file_reference.alternative.description';

// A.7.2.1: Beschreibung für tx_is_decorative-Feld hinzufügen
$GLOBALS['TCA']['sys_file_reference']['columns']['tx_is_decorative']['description'] = 'LLL:EXT:fahn_core/Resources/Private/Language/locallang_tca.xlf:sys_file_reference.tx_is_decorative.description';










