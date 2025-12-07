<?php

return [
    'ctrl' => [
        'title' => 'LLL:EXT:fahn_core_fahndung/Resources/Private/Language/locallang_db.xlf:tx_fahncorefahndung_domain_model_fahndung',
        'label' => 'title',
        'label_alt' => 'case_id',
        'label_alt_force' => true,
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'sortby' => 'sorting',
        'delete' => 'deleted',
        'versioningWS' => true,
        'origUid' => 't3ver_oid',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
        ],
        'searchFields' => 'title,description,case_id,location',
        'iconfile' => 'EXT:fahn_core_fahndung/Resources/Public/Icons/fahndung.svg',
    ],
    'types' => [
        '1' => [
            'showitem' => '
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                    title, case_id, description,
                --div--;LLL:EXT:fahn_core_fahndung/Resources/Private/Language/locallang_db.xlf:tabs.details,
                    date_of_crime, location, categories, images, is_published,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    hidden, starttime, endtime
            ',
        ],
    ],
    'columns' => [
        'sys_language_uid' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language',
            'config' => [
                'type' => 'language',
            ],
        ],
        'l10n_parent' => [
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.l18n_parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['', 0],
                ],
                'foreign_table' => 'tx_fahncorefahndung_domain_model_fahndung',
                'foreign_table_where' => 'AND tx_fahncorefahndung_domain_model_fahndung.pid=###CURRENT_PID### AND tx_fahncorefahndung_domain_model_fahndung.sys_language_uid IN (-1,0)',
                'default' => 0,
            ],
        ],
        'l10n_diffsource' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'hidden' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.hidden',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'items' => [
                    [
                        0 => '',
                        1 => '',
                    ],
                ],
            ],
        ],
        'starttime' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.starttime',
            'config' => [
                'type' => 'datetime',
                'default' => 0,
            ],
        ],
        'endtime' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.endtime',
            'config' => [
                'type' => 'datetime',
                'default' => 0,
                'range' => [
                    'upper' => mktime(0, 0, 0, 1, 1, 2038),
                ],
            ],
        ],
        'title' => [
            'exclude' => false,
            'label' => 'LLL:EXT:fahn_core_fahndung/Resources/Private/Language/locallang_db.xlf:tx_fahncorefahndung_domain_model_fahndung.title',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 255,
                'eval' => 'trim,required',
            ],
        ],
        'description' => [
            'exclude' => false,
            'label' => 'LLL:EXT:fahn_core_fahndung/Resources/Private/Language/locallang_db.xlf:tx_fahncorefahndung_domain_model_fahndung.description',
            'config' => [
                'type' => 'text',
                'enableRichtext' => true,
                'eval' => 'trim,required',
            ],
        ],
        'case_id' => [
            'exclude' => false,
            'label' => 'LLL:EXT:fahn_core_fahndung/Resources/Private/Language/locallang_db.xlf:tx_fahncorefahndung_domain_model_fahndung.case_id',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 100,
                'eval' => 'trim,required,unique',
            ],
        ],
        'categories' => [
            'exclude' => false,
            'label' => 'LLL:EXT:fahn_core_fahndung/Resources/Private/Language/locallang_db.xlf:tx_fahncorefahndung_domain_model_fahndung.categories',
            'config' => [
                'type' => 'category',
                'relationship' => 'manyToMany',
                'foreign_table' => 'sys_category',
                'MM' => 'tx_fahncorefahndung_fahndung_category_mm',
                'maxitems' => 10,
            ],
        ],
        'images' => [
            'exclude' => false,
            'label' => 'LLL:EXT:fahn_core_fahndung/Resources/Private/Language/locallang_db.xlf:tx_fahncorefahndung_domain_model_fahndung.images',
            'config' => [
                'type' => 'file',
                'allowed' => 'common-image-types',
                'maxitems' => 10,
                'appearance' => [
                    'createNewRelationLinkTitle' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:images.addFileReference',
                ],
                'overrideChildTca' => [
                    'types' => [
                        \TYPO3\CMS\Core\Resource\File::FILETYPE_IMAGE => [
                            'showitem' => '
                                --palette--;;imageoverlayPalette,
                                --palette--;;filePalette
                            ',
                        ],
                    ],
                    'columns' => [
                        'alternative' => [
                            'config' => [
                                'eval' => 'required',
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'date_of_crime' => [
            'exclude' => false,
            'label' => 'LLL:EXT:fahn_core_fahndung/Resources/Private/Language/locallang_db.xlf:tx_fahncorefahndung_domain_model_fahndung.date_of_crime',
            'config' => [
                'type' => 'datetime',
                'format' => 'datetime',
                'default' => 0,
            ],
        ],
        'location' => [
            'exclude' => false,
            'label' => 'LLL:EXT:fahn_core_fahndung/Resources/Private/Language/locallang_db.xlf:tx_fahncorefahndung_domain_model_fahndung.location',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 255,
                'eval' => 'trim',
            ],
        ],
        'is_published' => [
            'exclude' => false,
            'label' => 'LLL:EXT:fahn_core_fahndung/Resources/Private/Language/locallang_db.xlf:tx_fahncorefahndung_domain_model_fahndung.is_published',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'items' => [
                    [
                        0 => '',
                        1 => '',
                    ],
                ],
            ],
        ],
    ],
];









