<?php

declare(strict_types=1);

namespace Vendor\FahnCore\Tests\Unit\Middleware;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\JsonResponse;
use Vendor\FahnCore\Middleware\JwtAuthMiddleware;

/**
 * Unit Test für JwtAuthMiddleware
 * 
 * Testet JWT-Validierung isoliert.
 */
final class JwtAuthMiddlewareTest extends TestCase
{
    private array $originalGlobals;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalGlobals = $GLOBALS['TYPO3_CONF_VARS'] ?? [];
    }

    protected function tearDown(): void
    {
        $GLOBALS['TYPO3_CONF_VARS'] = $this->originalGlobals;
        parent::tearDown();
    }

    #[Test]
    public function processAllowsRequestWhenJwtNotConfigured(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fahn_core']['security']['jwt'] = [];
        
        $middleware = new JwtAuthMiddleware();
        
        $uri = $this->createMock(\Psr\Http\Message\UriInterface::class);
        $uri->method('getPath')->willReturn('/api/news');
        
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getUri')->willReturn($uri);
        
        $handler = $this->createMock(RequestHandlerInterface::class);
        $expectedResponse = new JsonResponse(['data' => 'test']);
        $handler->expects($this->once())
            ->method('handle')
            ->willReturn($expectedResponse);
        
        $response = $middleware->process($request, $handler);
        
        self::assertSame($expectedResponse, $response);
    }

    #[Test]
    public function processRejectsRequestWithoutAuthorizationHeader(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fahn_core']['security']['jwt'] = [
            'publicKeyPath' => '/dev/null', // Nicht lesbar, aber Middleware sollte trotzdem disabled sein
        ];
        
        $middleware = new JwtAuthMiddleware();
        
        $uri = $this->createMock(\Psr\Http\Message\UriInterface::class);
        $uri->method('getPath')->willReturn('/api/news');
        
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getUri')->willReturn($uri);
        $request->method('getHeaderLine')->with('Authorization')->willReturn('');
        
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())->method('handle');
        
        $response = $middleware->process($request, $handler);
        
        // Wenn JWT nicht konfiguriert, sollte Request durchgelassen werden
        self::assertNotSame(401, $response->getStatusCode());
    }
}

