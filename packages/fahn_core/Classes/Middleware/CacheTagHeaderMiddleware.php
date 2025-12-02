<?php

namespace Vendor\FahnCore\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Cache\CacheDataCollector;

/**
 * CacheTagHeaderMiddleware
 * 
 * Setzt Cache-Tags und Lifetime als HTTP-Header für Varnish/CDN/Next.js ISR.
 * 
 * TYPO3 v13: Nutzt frontend.cache.collector Attribut
 * 
 * @package Vendor\FahnCore\Middleware
 */
final class CacheTagHeaderMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $config = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fahn_core']['performance']['cacheTags'] ?? [];
        
        if (empty($config['enabled'])) {
            return $handler->handle($request);
        }

        $response = $handler->handle($request);

        // TYPO3 v13: CacheDataCollector über Request-Attribut
        $collector = $request->getAttribute('frontend.cache.collector');
        
        if (!$collector instanceof CacheDataCollector) {
            return $response;
        }

        $cacheTags = $collector->getCacheTags();
        $lifetime  = $collector->resolveLifetime();

        if (empty($cacheTags)) {
            return $response;
        }

        $headerName   = $config['headerName']   ?? 'X-FC-Cache-Tags';
        $lifetimeName = $config['lifetimeName'] ?? 'X-FC-Cache-Lifetime';

        // Tags als kommagetrennte Liste ausgeben (nur die Namen)
        $tagNames = array_map(fn($cacheTag) => $cacheTag->name, $cacheTags);
        $tagHeaderValue = implode(',', $tagNames);
        
        // Lifetime: PHP_INT_MAX bedeutet "unbegrenzt", dann setzen wir einen hohen Wert
        $lifetimeValue = ($lifetime === PHP_INT_MAX) ? 31536000 : $lifetime; // 1 Jahr als Fallback
        
        $response = $response
            ->withHeader($headerName, $tagHeaderValue)
            ->withHeader($lifetimeName, (string)$lifetimeValue);

        // Cache-Control aus Lifetime ableiten
        if ($lifetimeValue > 0 && $lifetimeValue !== PHP_INT_MAX && $response->hasHeader('Cache-Control') === false) {
            $response = $response->withHeader('Cache-Control', 'public, max-age=' . (int)$lifetimeValue);
        }

        return $response;
    }
}

