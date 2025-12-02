<?php

declare(strict_types=1);

namespace Vendor\FahnCore\Log\Processor;

use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Log\LogRecord;
use TYPO3\CMS\Core\Log\Processor\ProcessorInterface;

final class RequestIdProcessor implements ProcessorInterface
{
    public function processLogRecord(LogRecord $logRecord): LogRecord
    {
        $request = $GLOBALS['TYPO3_REQUEST'] ?? null;

        if ($request instanceof ServerRequest) {
            $requestId = $request->getHeaderLine('X-Request-ID') ?: null;
            if ($requestId) {
                $logRecord->addData(['request_id' => $requestId]);
            }
        }

        return $logRecord;
    }
}


