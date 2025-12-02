<?php

declare(strict_types=1);

namespace Vendor\FahnCore\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Cache\CacheManager;

final class HealthCheckMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();

        // Optional: Token-Schutz für /health/*
        $healthToken = getenv('HEALTH_CHECK_TOKEN');
        if (str_starts_with($path, '/health/') && $healthToken) {
            $requestToken = $request->getHeaderLine('X-Health-Token');
            if ($requestToken !== $healthToken) {
                return new JsonResponse(['error' => 'forbidden'], 403);
            }
        }

        if ($path === '/health/live') {
            return new JsonResponse([
                'status' => 'alive',
                'timestamp' => (new \DateTimeImmutable('now'))->format(DATE_ATOM),
            ], 200);
        }

        if ($path === '/health/ready') {
            $status = $this->checkDependencies();
            $code = $status['healthy'] ? 200 : 503;
            return new JsonResponse($status, $code);
        }

        return $handler->handle($request);
    }

    private function checkDependencies(): array
    {
        $result = [
            'healthy' => true,
            'checks' => [],
            'timestamp' => (new \DateTimeImmutable('now'))->format(DATE_ATOM),
        ];

        // DB
        try {
            $pool = GeneralUtility::makeInstance(ConnectionPool::class);
            $conn = $pool->getConnectionByName('Default');
            $conn->executeQuery('SELECT 1');
            $result['checks']['database'] = 'ok';
        } catch (\Throwable $e) {
            $result['checks']['database'] = 'failed: ' . $e->getMessage();
            $result['healthy'] = false;
        }

        // Redis
        try {
            /** @var CacheManager $cacheManager */
            $cacheManager = GeneralUtility::makeInstance(CacheManager::class);
            $cache = $cacheManager->getCache('pages');
            $cache->has('health_check_probe');
            $result['checks']['redis'] = 'ok';
        } catch (\Throwable $e) {
            $result['checks']['redis'] = 'failed: ' . $e->getMessage();
            // Redis als kritisch werten? → je nach Policy
            // $result['healthy'] = false;
        }

        return $result;
    }
}


