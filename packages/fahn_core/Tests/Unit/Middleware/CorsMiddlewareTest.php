<?php

declare(strict_types=1);

namespace Fahn\Core\Tests\Unit\Middleware;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\Response;
use Fahn\Core\Middleware\CorsMiddleware;

/**
 * Unit Test für CorsMiddleware
 * 
 * Testet die CORS-Logik isoliert ohne TYPO3-Bootstrap.
 */
final class CorsMiddlewareTest extends TestCase
{
    private CorsMiddleware $middleware;
    private array $originalGlobals;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Sichere originale GLOBALS
        $this->originalGlobals = $GLOBALS['TYPO3_CONF_VARS'] ?? [];
        
        // Setze Test-Konfiguration
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fahn_core']['security']['cors'] = [
            'allowedOrigins' => ['https://example.com', 'https://frontend.example.com'],
            'allowHeaders' => ['Content-Type', 'Authorization'],
            'allowMethods' => ['GET', 'POST', 'OPTIONS'],
        ];
        
        $this->middleware = new CorsMiddleware();
    }

    protected function tearDown(): void
    {
        // Stelle originale GLOBALS wieder her
        $GLOBALS['TYPO3_CONF_VARS'] = $this->originalGlobals;
        parent::tearDown();
    }

    #[Test]
    public function processAllowsRequestWithoutOrigin(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getHeaderLine')->with('Origin')->willReturn('');
        
        $handler = $this->createMock(RequestHandlerInterface::class);
        $expectedResponse = new Response();
        $handler->expects($this->once())
            ->method('handle')
            ->with($request)
            ->willReturn($expectedResponse);
        
        $response = $this->middleware->process($request, $handler);
        
        self::assertSame($expectedResponse, $response);
        self::assertFalse($response->hasHeader('Access-Control-Allow-Origin'));
    }

    #[Test]
    public function processRejectsRequestWithDisallowedOrigin(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getHeaderLine')->with('Origin')->willReturn('https://evil.com');
        $request->method('getMethod')->willReturn('GET');
        
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->never())->method('handle');
        
        $response = $this->middleware->process($request, $handler);
        
        self::assertSame(403, $response->getStatusCode());
    }

    #[Test]
    public function processHandlesOptionsPreflightRequest(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getHeaderLine')->with('Origin')->willReturn('https://example.com');
        $request->method('getMethod')->willReturn('OPTIONS');
        
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->never())->method('handle');
        
        $response = $this->middleware->process($request, $handler);
        
        self::assertSame(204, $response->getStatusCode());
        self::assertSame('https://example.com', $response->getHeaderLine('Access-Control-Allow-Origin'));
        self::assertTrue($response->hasHeader('Access-Control-Allow-Methods'));
    }

    #[Test]
    public function processAddsCorsHeadersToNormalRequest(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getHeaderLine')->with('Origin')->willReturn('https://example.com');
        $request->method('getMethod')->willReturn('GET');
        
        $handlerResponse = new Response('php://memory', 200);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->willReturn($handlerResponse);
        
        $response = $this->middleware->process($request, $handler);
        
        self::assertSame(200, $response->getStatusCode());
        self::assertSame('https://example.com', $response->getHeaderLine('Access-Control-Allow-Origin'));
        self::assertTrue($response->hasHeader('Access-Control-Allow-Credentials'));
    }
}









