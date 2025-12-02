<?php

declare(strict_types=1);

namespace Vendor\FahnCore\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\JsonResponse;

/**
 * Middleware zur Behandlung von 404-Fehlern als JSON in Headless-Umgebungen
 * 
 * Diese Middleware fängt 404-Fehler ab und gibt sie als JSON zurück,
 * wenn die Anfrage JSON erwartet (z.B. durch .json Suffix oder Accept-Header).
 */
class Json404Middleware implements MiddlewareInterface
{

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $response = $handler->handle($request);

        // Nur bei 404-Fehlern und wenn JSON erwartet wird
        if ($response->getStatusCode() === 404 && $this->expectsJson($request)) {
            // Prüfe, ob die Response bereits JSON ist
            $contentType = $response->getHeaderLine('Content-Type');
            if (str_contains($contentType, 'application/json')) {
                // Response ist bereits JSON, einfach zurückgeben
                return $response;
            }

            // HTML-Response in JSON konvertieren
            return new JsonResponse(
                [
                    'error' => 'Not Found',
                    'status' => 404,
                    'message' => 'The requested page does not exist or was inaccessible.',
                ],
                404
            );
        }

        return $response;
    }

    /**
     * Prüft, ob die Anfrage JSON erwartet
     */
    private function expectsJson(ServerRequestInterface $request): bool
    {
        // Prüfe .json Suffix in der URL
        $path = $request->getUri()->getPath();
        if (str_ends_with($path, '.json')) {
            return true;
        }

        // Prüfe Accept-Header
        $acceptHeader = $request->getHeaderLine('Accept');
        if (str_contains($acceptHeader, 'application/json')) {
            return true;
        }

        // Prüfe type=834 Parameter (für Rückwärtskompatibilität)
        $queryParams = $request->getQueryParams();
        if (isset($queryParams['type']) && (int)$queryParams['type'] === 834) {
            return true;
        }

        return false;
    }
}

