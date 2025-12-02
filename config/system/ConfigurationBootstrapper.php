<?php

namespace FahnCore\Config;

final class ConfigurationBootstrapper
{
    public static function build(): array
    {
        $env = $_ENV;
        $config = [
            'app' => [
                'key'  => self::optionalString($env, 'APP_KEY', ''),
                'env'  => self::optionalString($env, 'APP_ENV', 'development'),
                'name' => self::optionalString($env, 'APP_NAME', 'FAHN-CORE'),
            ],
            'db' => [
                'host' => self::optionalString($env, 'DB_HOST', ''),
                'port' => self::optionalInt($env, 'DB_PORT', 3306),
                'name' => self::optionalString($env, 'DB_NAME', ''),
                'user' => self::optionalString($env, 'DB_USER', ''),
                'pass' => self::optionalString($env, 'DB_PASSWORD', ''),
            ],
            'security' => [
                'cors' => [
                    'allowedOrigins' => self::optionalArray($env, 'TYPO3_CORS_ALLOWED_ORIGINS', []),
                    'allowHeaders'   => self::optionalArray($env, 'TYPO3_CORS_ALLOW_HEADERS', ['Content-Type', 'Authorization']),
                    'allowMethods'   => self::optionalArray($env, 'TYPO3_CORS_ALLOW_METHODS', ['GET', 'POST', 'OPTIONS']),
                ],
                'jwt' => [
                    'privateKeyPath' => self::optionalString($env, 'JWT_PRIVATE_KEY_PATH', ''),
                    'publicKeyPath'  => self::optionalString($env, 'JWT_PUBLIC_KEY_PATH', ''),
                    'ttl'            => self::optionalInt($env, 'JWT_TTL', 3600),
                    'refreshTtl'     => self::optionalInt($env, 'JWT_REFRESH_TTL', 604800),
                    'issuer'         => self::optionalString($env, 'JWT_ISS', 'fahn-core-auth'),
                    'audience'       => self::optionalString($env, 'JWT_AUD', 'fahn-core-frontend'),
                ],
                'csp' => [
                    'defaultSrc' => self::optionalString($env, 'CSP_DEFAULT_SRC', "'none'"),
                    'connectSrc' => self::optionalString($env, 'CSP_CONNECT_SRC', "'self'"),
                ],
            ],
            'redis' => [
                'host' => self::optionalString($env, 'REDIS_HOST', 'redis'),
                'port' => self::optionalInt($env, 'REDIS_PORT', 6379),
                'prefix' => self::optionalString($env, 'FAHN_CORE_CACHE_PREFIX', 'fahncore_'),
            ],
            'cacheTags' => [
                'enabled'      => self::optionalBool($env, 'CACHE_TAG_ENABLE', true),
                'headerName'   => self::optionalString($env, 'CACHE_TAG_HEADER_NAME', 'X-FC-Cache-Tags'),
                'lifetimeName' => self::optionalString($env, 'CACHE_TAG_LIFETIME_HEADER_NAME', 'X-FC-Cache-Lifetime'),
            ],
            'cdn' => [
                'storageProxyEnabled' => self::optionalBool($env, 'HEADLESS_STORAGE_PROXY_ENABLED', true),
                'host'                => self::optionalString($env, 'CDN_HOST', ''),
            ],
        ];

        return $config;
    }

    // ========================================
    // HELPER METHODS
    // ========================================

    private static function requireString(array $env, string $key, int $minLength = 1): string
    {
        $value = trim($env[$key] ?? '');
        if ($value === '' || strlen($value) < $minLength) {
            throw new \RuntimeException("Missing or invalid ENV variable: {$key}");
        }
        return $value;
    }

    private static function optionalString(array $env, string $key, string $default): string
    {
        $value = trim($env[$key] ?? '');
        return $value === '' ? $default : $value;
    }

    private static function optionalInt(array $env, string $key, int $default): int
    {
        $value = trim($env[$key] ?? '');
        return $value === '' ? $default : (int)$value;
    }

    private static function optionalBool(array $env, string $key, bool $default): bool
    {
        $value = strtolower(trim($env[$key] ?? ''));
        if ($value === '') {
            return $default;
        }
        return in_array($value, ['1', 'true', 'yes', 'on'], true);
    }

    private static function optionalArray(array $env, string $key, array $default): array
    {
        $value = trim($env[$key] ?? '');
        if ($value === '') {
            return $default;
        }
        // Comma-separated string to array
        return array_map('trim', explode(',', $value));
    }

    private static function requireEnum(array $env, string $key, array $allowed): string
    {
        $value = strtolower(trim($env[$key] ?? ''));
        if (!in_array($value, $allowed, true)) {
            throw new \RuntimeException("Invalid ENV value for {$key}, allowed: " . implode(',', $allowed));
        }
        return $value;
    }
}

