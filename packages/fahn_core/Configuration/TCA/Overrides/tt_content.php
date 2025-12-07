<?php

declare(strict_types=1);

defined('TYPO3') or die();

// A.7.1.2: Härtung der Bildreferenzen für bestimmte Inhaltstypen
// textmedia – Alt-Text verpflichtend für informative Bilder

if (isset($GLOBALS['TCA']['tt_content']['types']['textmedia'])) {
    $GLOBALS['TCA']['tt_content']['types']['textmedia']['columnsOverrides']['assets']['config']['overrideChildTca']['columns']['alternative']['config']['required'] = true;
    $GLOBALS['TCA']['tt_content']['types']['textmedia']['columnsOverrides']['assets']['config']['overrideChildTca']['columns']['alternative']['config']['eval'] = 'trim';
    
    // Optional: Titel-Feld als Unterstützung für Tooltips
    $GLOBALS['TCA']['tt_content']['types']['textmedia']['columnsOverrides']['assets']['config']['overrideChildTca']['columns']['title']['config']['eval'] = 'trim';
}









