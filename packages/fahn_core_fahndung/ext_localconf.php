<?php

defined('TYPO3') or die();

// Register Fahndung Controller Plugin
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'FahnCoreFahndung',
    'Api',
    [
        \Fahn\CoreFahndung\Controller\Api\FahndungController::class => 'list,show,create,update,delete',
    ],
    [
        \Fahn\CoreFahndung\Controller\Api\FahndungController::class => 'list,show,create,update,delete',
    ]
);