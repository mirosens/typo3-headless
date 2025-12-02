<?php return array(
    'root' => array(
        'name' => 'fahn-core/site-package',
        'pretty_version' => '1.0.0+no-version-set',
        'version' => '1.0.0.0',
        'reference' => null,
        'type' => 'typo3-cms-extension',
        'install_path' => __DIR__ . '/../../',
        'aliases' => array(),
        'dev' => true,
    ),
    'versions' => array(
        'fahn-core/site-package' => array(
            'pretty_version' => '1.0.0+no-version-set',
            'version' => '1.0.0.0',
            'reference' => null,
            'type' => 'typo3-cms-extension',
            'install_path' => __DIR__ . '/../../',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
        'lw/typo3cms-installers' => array(
            'dev_requirement' => false,
            'replaced' => array(
                0 => '*',
            ),
        ),
        'netresearch/composer-installers' => array(
            'dev_requirement' => false,
            'replaced' => array(
                0 => '*',
            ),
        ),
        'typo3/cms-composer-installers' => array(
            'pretty_version' => 'v5.0.1',
            'version' => '5.0.1.0',
            'reference' => '444a228d3ae4320d7ba0b769cfab008b0c09443c',
            'type' => 'composer-plugin',
            'install_path' => __DIR__ . '/../typo3/cms-composer-installers',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
    ),
);
