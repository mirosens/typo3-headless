<?php

namespace Vendor\FahnCore\Middleware;

use Lcobucci\JWT\Configuration as JwtConfiguration;
use Lcobucci\JWT\Token\InvalidTokenStructure;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\PermittedFor;
use Lcobucci\JWT\Validation\Constraint\ValidAt;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\JsonResponse;

final class JwtAuthMiddleware implements MiddlewareInterface
{
    private string $issuer;
    private string $audience;

    private ?JwtConfiguration $jwtConfig = null;
    private bool $enabled = false;

    public function __construct()
    {
        $config = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fahn_core']['security']['jwt'] ?? [];
        $publicKeyPath = $config['publicKeyPath'] ?? '';
        $this->issuer   = $config['issuer'] ?? 'fahn-core-auth';
        $this->audience = $config['audience'] ?? 'fahn-core-frontend';

        // Nur initialisieren, wenn der Pfad gesetzt und die Datei lesbar ist
        if ($publicKeyPath !== '' && is_readable($publicKeyPath)) {
            try {
                $this->jwtConfig = JwtConfiguration::forAsymmetricSigner(
                    new \Lcobucci\JWT\Signer\Rsa\Sha256(),
                    \Lcobucci\JWT\Signer\Key\InMemory::plainText(''),
                    \Lcobucci\JWT\Signer\Key\InMemory::file($publicKeyPath)
                );
                $this->enabled = true;
            } catch (\Exception $e) {
                // Log error but don't break TYPO3
                error_log('JWT Auth Middleware initialization failed: ' . $e->getMessage());
                $this->enabled = false;
            }
        } else {
            // Wenn kein Pfad gesetzt oder Datei nicht lesbar, Middleware deaktivieren
            error_log('JWT Auth Middleware disabled: Public key path not configured or not readable: ' . $publicKeyPath);
            $this->enabled = false;
        }
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Wenn JWT nicht konfiguriert ist, einfach durchlassen
        if (!$this->enabled || $this->jwtConfig === null) {
            return $handler->handle($request);
        }

        $path = $request->getUri()->getPath();

        // Optional: Nur auf /api/* anwenden
        if (strpos($path, '/api/') !== 0) {
            return $handler->handle($request);
        }

        $authHeader = $request->getHeaderLine('Authorization');
        if (!$authHeader || stripos($authHeader, 'Bearer ') !== 0) {
            return $this->unauthorized('Missing or invalid Authorization header');
        }

        $tokenString = trim(substr($authHeader, 7));

        try {
            $token = $this->jwtConfig->parser()->parse($tokenString);
        } catch (InvalidTokenStructure $e) {
            return $this->unauthorized('Invalid token structure');
        }

        // Validierungs-Constraints
        $constraints = [
            new IssuedBy($this->issuer),
            new PermittedFor($this->audience),
            new ValidAt(new \DateTimeImmutable()),
        ];

        if (!$this->jwtConfig->validator()->validate($token, ...$constraints)) {
            return $this->unauthorized('Token validation failed');
        }

        // Claims ins Request-Attribut legen
        $request = $request->withAttribute('jwt.claims', $token->claims()->all());
        $request = $request->withAttribute('jwt.token', $token);

        return $handler->handle($request);
    }

    private function unauthorized(string $message): ResponseInterface
    {
        return new JsonResponse([
            'error' => 'unauthorized',
            'message' => $message,
        ], 401);
    }
}

