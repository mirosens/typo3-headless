<?php

namespace Vendor\FahnCore\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\Response;

final class CorsMiddleware implements MiddlewareInterface
{
    private array $allowedOrigins;
    private array $allowedHeaders;
    private array $allowedMethods;

    public function __construct()
    {
        $config = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fahn_core']['security']['cors'] ?? [];
        $this->allowedOrigins = $config['allowedOrigins'] ?? [];
        $this->allowedHeaders = $config['allowHeaders'] ?? ['Content-Type', 'Authorization'];
        $this->allowedMethods = $config['allowMethods'] ?? ['GET', 'POST', 'OPTIONS'];
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $origin = $request->getHeaderLine('Origin');
        $method = strtoupper($request->getMethod());

        // Kein Origin → kein CORS, normal weiter
        if ($origin === '') {
            return $handler->handle($request);
        }

        if (!$this->isOriginAllowed($origin)) {
            // Origin nicht erlaubt → 403
            return (new Response())
                ->withStatus(403, 'Origin not allowed');
        }

        // OPTIONS-Preflight: sofort 204 + CORS-Header, kein TSFE-Bootstrap
        if ($method === 'OPTIONS') {
            $response = new Response('php://memory', 204);
            return $this->withCorsHeaders($response, $origin);
        }

        // Normale Requests: Handler ausführen, dann CORS-Header ergänzen
        $response = $handler->handle($request);

        return $this->withCorsHeaders($response, $origin);
    }

    private function isOriginAllowed(string $origin): bool
    {
        if (empty($this->allowedOrigins)) {
            return false;
        }

        return in_array($origin, $this->allowedOrigins, true);
    }

    private function withCorsHeaders(ResponseInterface $response, string $origin): ResponseInterface
    {
        return $response
            ->withHeader('Access-Control-Allow-Origin', $origin)
            ->withHeader('Vary', 'Origin')
            ->withHeader('Access-Control-Allow-Methods', implode(',', $this->allowedMethods))
            ->withHeader('Access-Control-Allow-Headers', implode(',', $this->allowedHeaders))
            ->withHeader('Access-Control-Allow-Credentials', 'true')
            ->withHeader('Access-Control-Max-Age', '86400');
    }
}







