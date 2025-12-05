<?php

namespace Vendor\FahnCore\Hooks;

use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

final class CspHeaderHook
{
    public function addCspHeader(array $params, TypoScriptFrontendController $tsfe): void
    {
        $config = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fahn_core']['security']['csp'] ?? [];
        $cspDefault = $config['defaultSrc'] ?? "'none'";
        $cspConnect = $config['connectSrc'] ?? "'self'";

        $cspHeaderValue = sprintf(
            "default-src %s; connect-src %s;",
            $cspDefault,
            $cspConnect
        );

        header('Content-Security-Policy: ' . $cspHeaderValue);
    }
}









