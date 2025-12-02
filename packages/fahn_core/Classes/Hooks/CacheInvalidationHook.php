<?php

namespace Vendor\FahnCore\Hooks;

use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Cache\Event\CacheFlushEvent;
use TYPO3\CMS\Core\Http\RequestFactory;

/**
 * CacheInvalidationHook
 * 
 * Sendet Webhook-Requests an Next.js/Varnish bei Cache-Invalidierungen.
 * 
 * @package Vendor\FahnCore\Hooks
 */
final class CacheInvalidationHook
{
    public function __construct(
        private readonly RequestFactory $requestFactory,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Wird nach dem Löschen/Leeren von Caches aufgerufen
     * 
     * Hinweis: CacheFlushEvent enthält Cache-Gruppen, nicht Tags.
     * Für tag-basierte Invalidierung sollte ein DataHandler-Hook verwendet werden.
     * Dieser Hook ist für vollständige Cache-Flushes gedacht.
     */
    public function clearCachePostProc(CacheFlushEvent $event): void
    {
        $groups = $event->getGroups();
        $url = getenv('NEXTJS_REVALIDATE_URL');
        
        // Nur bei vollständigem Cache-Flush (alle Gruppen) Webhook senden
        if (!$url || !empty($groups)) {
            return;
        }

        try {
            // Bei vollständigem Cache-Flush: alle Tags invalidieren
            $this->requestFactory->request(
                rtrim($url, '/') . '/api/revalidate',
                'POST',
                [
                    'headers' => ['Content-Type' => 'application/json'],
                    'body'    => json_encode(['tags' => ['*']], JSON_THROW_ON_ERROR),
                ]
            );
        } catch (\Throwable $e) {
            $this->logger->error('Next.js revalidate webhook failed', [
                'exception' => $e,
                'groups' => $groups,
            ]);
        }
    }
}

