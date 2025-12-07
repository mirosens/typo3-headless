<?php

defined('TYPO3') or die();

call_user_func(function () {
    // Registrierung des Fahndung‑API‑Plugins
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
        'FahnCoreFahndung',
        'Api',
        [\Fahn\CoreFahndung\Controller\FahndungController::class => 'list, show, create, update, delete'],
        // nonCacheableActions – alle CRUD‑Actions dürfen nicht im Page‑Cache landen
        [\Fahn\CoreFahndung\Controller\FahndungController::class => 'list, show, create, update, delete']
    );
});

